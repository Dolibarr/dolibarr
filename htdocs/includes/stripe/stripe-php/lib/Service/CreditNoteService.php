<?php

// File generated from our OpenAPI spec

namespace Stripe\Service;

class CreditNoteService extends \Stripe\Service\AbstractService
{
    /**
     * Returns a list of credit notes.
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection<\Stripe\CreditNote>
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/credit_notes', $params, $opts);
    }

    /**
     * When retrieving a credit note, you’ll get a <strong>lines</strong> property
     * containing the the first handful of those items. There is also a URL where you
     * can retrieve the full (paginated) list of line items.
     *
     * @param string $parentId
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection<\Stripe\CreditNoteLineItem>
     */
    public function allLines($parentId, $params = null, $opts = null)
    {
        return $this->requestCollection('get', $this->buildPath('/v1/credit_notes/%s/lines', $parentId), $params, $opts);
    }

    /**
     * Issue a credit note to adjust the amount of a finalized invoice. For a
     * <code>status=open</code> invoice, a credit note reduces its
     * <code>amount_due</code>. For a <code>status=paid</code> invoice, a credit note
     * does not affect its <code>amount_due</code>. Instead, it can result in any
     * combination of the following:.
     *
     * <ul> <li>Refund: create a new refund (using <code>refund_amount</code>) or link
     * an existing refund (using <code>refund</code>).</li> <li>Customer balance
     * credit: credit the customer’s balance (using <code>credit_amount</code>) which
     * will be automatically applied to their next invoice when it’s finalized.</li>
     * <li>Outside of Stripe credit: record the amount that is or will be credited
     * outside of Stripe (using <code>out_of_band_amount</code>).</li> </ul>
     *
     * For post-payment credit notes the sum of the refund, credit and outside of
     * Stripe amounts must equal the credit note total.
     *
     * You may issue multiple credit notes for an invoice. Each credit note will
     * increment the invoice’s <code>pre_payment_credit_notes_amount</code> or
     * <code>post_payment_credit_notes_amount</code> depending on its
     * <code>status</code> at the time of credit note creation.
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\CreditNote
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/credit_notes', $params, $opts);
    }

    /**
     * Get a preview of a credit note without creating it.
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\CreditNote
     */
    public function preview($params = null, $opts = null)
    {
        return $this->request('get', '/v1/credit_notes/preview', $params, $opts);
    }

    /**
     * When retrieving a credit note preview, you’ll get a <strong>lines</strong>
     * property containing the first handful of those items. This URL you can retrieve
     * the full (paginated) list of line items.
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection<\Stripe\CreditNoteLineItem>
     */
    public function previewLines($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/credit_notes/preview/lines', $params, $opts);
    }

    /**
     * Retrieves the credit note object with the given identifier.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\CreditNote
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/credit_notes/%s', $id), $params, $opts);
    }

    /**
     * Updates an existing credit note.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\CreditNote
     */
    public function update($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/credit_notes/%s', $id), $params, $opts);
    }

    /**
     * Marks a credit note as void. Learn more about <a
     * href="/docs/billing/invoices/credit-notes#voiding">voiding credit notes</a>.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\CreditNote
     */
    public function voidCreditNote($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/credit_notes/%s/void', $id), $params, $opts);
    }
}
