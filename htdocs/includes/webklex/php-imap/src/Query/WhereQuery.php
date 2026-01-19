<?php
/*
* File:     Query.php
* Category: -
* Author:   M. Goldenbaum
* Created:  21.07.18 18:54
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Query;

use Closure;
use Illuminate\Support\Str;
use Webklex\PHPIMAP\Exceptions\InvalidWhereQueryCriteriaException;
use Webklex\PHPIMAP\Exceptions\MethodNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageSearchValidationException;

/**
 * Class WhereQuery
 *
 * @package Webklex\PHPIMAP\Query
 *
 * @method WhereQuery all()
 * @method WhereQuery answered()
 * @method WhereQuery deleted()
 * @method WhereQuery new()
 * @method WhereQuery old()
 * @method WhereQuery recent()
 * @method WhereQuery seen()
 * @method WhereQuery unanswered()
 * @method WhereQuery undeleted()
 * @method WhereQuery unflagged()
 * @method WhereQuery unseen()
 * @method WhereQuery not()
 * @method WhereQuery unkeyword($value)
 * @method WhereQuery to($value)
 * @method WhereQuery text($value)
 * @method WhereQuery subject($value)
 * @method WhereQuery since($date)
 * @method WhereQuery on($date)
 * @method WhereQuery keyword($value)
 * @method WhereQuery from($value)
 * @method WhereQuery flagged()
 * @method WhereQuery cc($value)
 * @method WhereQuery body($value)
 * @method WhereQuery before($date)
 * @method WhereQuery bcc($value)
 * @method WhereQuery inReplyTo($value)
 * @method WhereQuery messageId($value)
 *
 * @mixin Query
 */
class WhereQuery extends Query {

    /**
     * @var array $available_criteria
     */
    protected array $available_criteria = [
        'OR', 'AND',
        'ALL', 'ANSWERED', 'BCC', 'BEFORE', 'BODY', 'CC', 'DELETED', 'FLAGGED', 'FROM', 'KEYWORD',
        'NEW', 'NOT', 'OLD', 'ON', 'RECENT', 'SEEN', 'SINCE', 'SUBJECT', 'TEXT', 'TO',
        'UNANSWERED', 'UNDELETED', 'UNFLAGGED', 'UNKEYWORD', 'UNSEEN', 'UID'
    ];

    /**
     * Magic method in order to allow alias usage of all "where" methods in an optional connection with "NOT"
     * @param string $name
     * @param array|null $arguments
     *
     * @return mixed
     * @throws InvalidWhereQueryCriteriaException
     * @throws MethodNotFoundException
     */
    public function __call(string $name, ?array $arguments) {
        $that = $this;

        $name = Str::camel($name);

        if (strtolower(substr($name, 0, 3)) === 'not') {
            $that = $that->whereNot();
            $name = substr($name, 3);
        }

        if (!str_contains(strtolower($name), "where")) {
            $method = 'where' . ucfirst($name);
        } else {
            $method = lcfirst($name);
        }

        if (method_exists($this, $method) === true) {
            return call_user_func_array([$that, $method], $arguments);
        }

        throw new MethodNotFoundException("Method " . self::class . '::' . $method . '() is not supported');
    }

    /**
     * Validate a given criteria
     * @param $criteria
     *
     * @return string
     * @throws InvalidWhereQueryCriteriaException
     */
    protected function validate_criteria($criteria): string {
        $command = strtoupper($criteria);
        if (str_starts_with($command, "CUSTOM ")) {
            return substr($criteria, 7);
        }
        if (in_array($command, $this->available_criteria) === false) {
            throw new InvalidWhereQueryCriteriaException("Invalid imap search criteria: $command");
        }

        return $criteria;
    }

    /**
     * Register search parameters
     * @param mixed $criteria
     * @param mixed $value
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     *
     * Examples:
     * $query->from("someone@email.tld")->seen();
     * $query->whereFrom("someone@email.tld")->whereSeen();
     * $query->where([["FROM" => "someone@email.tld"], ["SEEN"]]);
     * $query->where(["FROM" => "someone@email.tld"])->where(["SEEN"]);
     * $query->where(["FROM" => "someone@email.tld", "SEEN"]);
     * $query->where("FROM", "someone@email.tld")->where("SEEN");
     */
    public function where(mixed $criteria, mixed $value = null): static {
        if (is_array($criteria)) {
            foreach ($criteria as $key => $value) {
                if (is_numeric($key)) {
                    $this->where($value);
                }else{
                    $this->where($key, $value);
                }
            }
        } else {
            $this->push_search_criteria($criteria, $value);
        }

        return $this;
    }

    /**
     * Push a given search criteria and value pair to the search query
     * @param $criteria string
     * @param $value mixed
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    protected function push_search_criteria(string $criteria, mixed $value): void {
        $criteria = $this->validate_criteria($criteria);
        $value = $this->parse_value($value);

        if ($value === '') {
            $this->query->push([$criteria]);
        } else {
            $this->query->push([$criteria, $value]);
        }
    }

    /**
     * @param Closure|null $closure
     *
     * @return $this
     */
    public function orWhere(?Closure $closure = null): static {
        $this->query->push(['OR']);
        if ($closure !== null) $closure($this);

        return $this;
    }

    /**
     * @param Closure|null $closure
     *
     * @return $this
     */
    public function andWhere(?Closure $closure = null): static {
        $this->query->push(['AND']);
        if ($closure !== null) $closure($this);

        return $this;
    }

    /**
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereAll(): static {
        return $this->where('ALL');
    }

    /**
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereAnswered(): static {
        return $this->where('ANSWERED');
    }

    /**
     * @param string $value
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereBcc(string $value): static {
        return $this->where('BCC', $value);
    }

    /**
     * @param mixed $value
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     * @throws MessageSearchValidationException
     */
    public function whereBefore(mixed $value): static {
        $date = $this->parse_date($value);
        return $this->where('BEFORE', $date);
    }

    /**
     * @param string $value
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereBody(string $value): static {
        return $this->where('BODY', $value);
    }

    /**
     * @param string $value
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereCc(string $value): static {
        return $this->where('CC', $value);
    }

    /**
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereDeleted(): static {
        return $this->where('DELETED');
    }

    /**
     * @param string $value
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereFlagged(string $value): static {
        return $this->where('FLAGGED', $value);
    }

    /**
     * @param string $value
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereFrom(string $value): static {
        return $this->where('FROM', $value);
    }

    /**
     * @param string $value
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereKeyword(string $value): static {
        return $this->where('KEYWORD', $value);
    }

    /**
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereNew(): static {
        return $this->where('NEW');
    }

    /**
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereNot(): static {
        return $this->where('NOT');
    }

    /**
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereOld(): static {
        return $this->where('OLD');
    }

    /**
     * @param mixed $value
     *
     * @return $this
     * @throws MessageSearchValidationException
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereOn(mixed $value): static {
        $date = $this->parse_date($value);
        return $this->where('ON', $date);
    }

    /**
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereRecent(): static {
        return $this->where('RECENT');
    }

    /**
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereSeen(): static {
        return $this->where('SEEN');
    }

    /**
     * @param mixed $value
     *
     * @return $this
     * @throws MessageSearchValidationException
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereSince(mixed $value): static {
        $date = $this->parse_date($value);
        return $this->where('SINCE', $date);
    }

    /**
     * @param string $value
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereSubject(string $value): static {
        return $this->where('SUBJECT', $value);
    }

    /**
     * @param string $value
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereText(string $value): static {
        return $this->where('TEXT', $value);
    }

    /**
     * @param string $value
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereTo(string $value): static {
        return $this->where('TO', $value);
    }

    /**
     * @param string $value
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUnkeyword(string $value): static {
        return $this->where('UNKEYWORD', $value);
    }

    /**
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUnanswered(): static {
        return $this->where('UNANSWERED');
    }

    /**
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUndeleted(): static {
        return $this->where('UNDELETED');
    }

    /**
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUnflagged(): static {
        return $this->where('UNFLAGGED');
    }

    /**
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUnseen(): static {
        return $this->where('UNSEEN');
    }

    /**
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereNoXSpam(): static {
        return $this->where("CUSTOM X-Spam-Flag NO");
    }

    /**
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereIsXSpam(): static {
        return $this->where("CUSTOM X-Spam-Flag YES");
    }

    /**
     * Search for a specific header value
     * @param $header
     * @param $value
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereHeader($header, $value): static {
        return $this->where("CUSTOM HEADER $header $value");
    }

    /**
     * Search for a specific message id
     * @param $messageId
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereMessageId($messageId): static {
        return $this->whereHeader("Message-ID", $messageId);
    }

    /**
     * Search for a specific message id
     * @param $messageId
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereInReplyTo($messageId): static {
        return $this->whereHeader("In-Reply-To", $messageId);
    }

    /**
     * @param $country_code
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereLanguage($country_code): static {
        return $this->where("Content-Language $country_code");
    }

    /**
     * Get message be it UID.
     *
     * @param int|string $uid
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUid(int|string $uid): static {
        return $this->where('UID', $uid);
    }

    /**
     * Get messages by their UIDs.
     *
     * @param array<int, int> $uids
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUidIn(array $uids): static {
        $uids = implode(',', $uids);
        return $this->where('UID', $uids);
    }

    /**
     * Apply the callback if the given "value" is truthy.
     * copied from @url https://github.com/laravel/framework/blob/8.x/src/Illuminate/Support/Traits/Conditionable.php
     *
     * @param mixed $value
     * @param callable $callback
     * @param callable|null $default
     * @return $this|null
     */
    public function when(mixed $value, callable $callback, ?callable $default = null): mixed {
        if ($value) {
            return $callback($this, $value) ?: $this;
        } elseif ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * Apply the callback if the given "value" is falsy.
     * copied from @url https://github.com/laravel/framework/blob/8.x/src/Illuminate/Support/Traits/Conditionable.php
     *
     * @param mixed $value
     * @param callable $callback
     * @param callable|null $default
     * @return $this|mixed
     */
    public function unless(mixed $value, callable $callback, ?callable $default = null): mixed {
        if (!$value) {
            return $callback($this, $value) ?: $this;
        } elseif ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * Get all available search criteria
     *
     * @return array|string[]
     */
    public function getAvailableCriteria(): array {
        return $this->available_criteria;
    }
}