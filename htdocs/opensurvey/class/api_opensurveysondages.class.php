<?php
/* Copyright (C) 2025   MDW						<mdeweerd@users.noreply.github.com>
 *  Copyright (C) 2025   Jessica Kowal		<jessicakowal69@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/opensurvey/class/opensurveysondage.class.php';


/**
 * API class for Opensurvey Sondage
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Opensurveysondages extends DolibarrApi
{
    /**
     * @var string[]    Mandatory fields, checked when create and update object
     */
    public static $FIELDS = array(
        'title',
        'format',
        'date_fin'
    );

    /**
     * @var Opensurveysondage {@type Opensurveysondage}
     */
    public $opensurveysondage;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $db;

        $this->db = $db;
        $this->opensurveysondage = new Opensurveysondage($this->db);
    }

    /**
     * Get a survey
     *
     * @param   string  $id     ID of Survey
     * @return  Object          Object with cleaned properties
     *
     * @url GET {id}
     * @throws RestException
     */
    public function get($id)
    {
        if (!DolibarrApiAccess::$user->hasRight('opensurvey', 'read')) {
            throw new RestException(403);
        }

        $result = $this->opensurveysondage->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Survey not found');
        }

        if (!DolibarrApi::_checkAccessToResource('opensurveysondage', $this->opensurveysondage->id_sondage)) {
            throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->opensurveysondage->fetch_lines();
        return $this->_cleanObjectDatas($this->opensurveysondage);
    }

    /**
 * List surveys
 *
 * Get a list of surveys
 *
 * @param   string  $sortfield          Sort field
 * @param   string  $sortorder          Sort order
 * @param   int     $limit              List limit
 * @param   int     $page               Page number
 * @param   string  $sqlfilters         Other criteria to filter answers separated by a comma
 * @param   string  $properties         Restrict the data returned to these properties
 * @param   bool    $pagination_data    If true, response includes pagination data
 * @return  array                       Array of survey objects
 *
 * @url GET /
 * @throws RestException
 */
public function index($sortfield = "t.id_sondage", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '', $pagination_data = false)
{
    if (!DolibarrApiAccess::$user->hasRight('opensurvey', 'read')) {
        throw new RestException(403);
    }

    $obj_ret = array();

    $sql = "SELECT t.id_sondage";
    $sql .= " FROM ".MAIN_DB_PREFIX."opensurvey_sondage AS t";
    $sql .= ' WHERE t.entity IN ('.getEntity('opensurveysondage').')';

    // Add sql filters
    if ($sqlfilters) {
        $errormessage = '';
        $sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
        if ($errormessage) {
            throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
        }
    }

    $sqlTotals = str_replace('SELECT t.id_sondage', 'SELECT count(t.id_sondage) as total', $sql);

    // Vérifier que le champ de tri existe
    $allowedSortFields = array('id_sondage', 'title', 'date_fin', 'status', 'fk_user_creat', 'date_creation');
    if (!in_array(str_replace('t.', '', $sortfield), $allowedSortFields)) {
        $sortfield = 't.id_sondage';
    }
    
    $sql .= $this->db->order($sortfield, $sortorder);
    
    if ($limit) {
        if ($page < 0) {
            $page = 0;
        }
        $offset = $limit * $page;

        $sql .= $this->db->plimit($limit + 1, $offset);
    }

    dol_syslog("API list query: " . $sql, LOG_DEBUG);
    $result = $this->db->query($sql);

    if ($result) {
        $num = $this->db->num_rows($result);
        $min = min($num, ($limit <= 0 ? $num : $limit));
        $i = 0;
        while ($i < $min) {
            $obj = $this->db->fetch_object($result);
            $survey_static = new Opensurveysondage($this->db);
            if ($survey_static->fetch($obj->id_sondage)) {
                $obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($survey_static), $properties);
            }
            $i++;
        }
    } else {
        throw new RestException(503, 'Error when retrieve Survey list : '.$this->db->lasterror());
    }

    if ($pagination_data) {
        $totalsResult = $this->db->query($sqlTotals);
        if ($totalsResult) {
            $objTotal = $this->db->fetch_object($totalsResult);
            $total = $objTotal ? $objTotal->total : 0;
        } else {
            $total = 0;
        }

        $tmp = $obj_ret;
        $obj_ret = [];

        $obj_ret['data'] = $tmp;
        $obj_ret['pagination'] = [
            'total' => (int) $total,
            'page' => $page, //count starts from 0
            'page_count' => $limit > 0 ? ceil((int) $total / $limit) : 1,
            'limit' => $limit
        ];
    }

    return $obj_ret;
}

      /**
 * Create a survey
 *
 * @param   array   $request_data   Request data
 * @return  string                  ID of Survey
 *
 * @url POST /
 * @throws RestException
 */
public function post($request_data = null)
{
    dol_syslog("POST /opensurveysondages called with data: " . json_encode($request_data), LOG_DEBUG);
    
    if (!DolibarrApiAccess::$user->hasRight('opensurvey', 'write')) {
        throw new RestException(403, "Insufficient rights");
    }

    // Check mandatory fields
    $result = $this->_validate($request_data);

    // Generate unique ID if not provided
if (empty($request_data['id_sondage'])) {
    // Générer un ID de 16 caractères maximum (standard Dolibarr pour opensurvey)
    $request_data['id_sondage'] = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(12))), 0, 16);
}

// Assurer que l'ID ne dépasse pas 16 caractères
if (strlen($request_data['id_sondage']) > 16) {
    $request_data['id_sondage'] = substr($request_data['id_sondage'], 0, 16);
}

    foreach ($request_data as $field => $value) {
        if ($field === 'caller') {
            $this->opensurveysondage->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
            continue;
        }

        $this->opensurveysondage->$field = $this->_checkValForAPI($field, $value, $this->opensurveysondage);
    }

    // Set default values
    if (empty($this->opensurveysondage->status)) {
        $this->opensurveysondage->status = Opensurveysondage::STATUS_VALIDATED;
    }
    if (empty($this->opensurveysondage->fk_user_creat)) {
        $this->opensurveysondage->fk_user_creat = DolibarrApiAccess::$user->id;
    }

    if ($this->opensurveysondage->create(DolibarrApiAccess::$user) < 0) {
        throw new RestException(500, "Error creating survey", array_merge(array($this->opensurveysondage->error), $this->opensurveysondage->errors));
    }

    return $this->opensurveysondage->id_sondage;
}

    /**
     * Update survey
     *
     * @param   string  $id             ID of Survey to update
     * @param   array   $request_data   Survey data
     * @return  Object                  Updated object
     *
     * @url PUT {id}
     * @throws RestException
     */
    public function put($id, $request_data = null)
    {
        if (!DolibarrApiAccess::$user->hasRight('opensurvey', 'write')) {
            throw new RestException(403);
        }

        $result = $this->opensurveysondage->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Survey not found');
        }

        if (!DolibarrApi::_checkAccessToResource('opensurveysondage', $this->opensurveysondage->id_sondage)) {
            throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        
        foreach ($request_data as $field => $value) {
            if ($field == 'id_sondage') {
                continue;
            }
            if ($field === 'caller') {
                $this->opensurveysondage->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
                continue;
            }

            if ($field == 'array_options' && is_array($value)) {
                foreach ($value as $index => $val) {
                    $this->opensurveysondage->array_options[$index] = $this->_checkValForAPI($field, $val, $this->opensurveysondage);
                }
                continue;
            }

            $this->opensurveysondage->$field = $this->_checkValForAPI($field, $value, $this->opensurveysondage);
        }

        if ($this->opensurveysondage->update(DolibarrApiAccess::$user) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, $this->opensurveysondage->error);
        }
    }

    /**
     * Delete survey
     *
     * @param   string  $id     Survey ID
     * @return  array
     *
     * @url DELETE {id}
     * @throws RestException
     */
    public function delete($id)
    {
        if (!DolibarrApiAccess::$user->hasRight('opensurvey', 'delete')) {
            throw new RestException(403);
        }

        $result = $this->opensurveysondage->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Survey not found');
        }

        if (!DolibarrApi::_checkAccessToResource('opensurveysondage', $this->opensurveysondage->id_sondage)) {
            throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (!$this->opensurveysondage->delete(DolibarrApiAccess::$user)) {
            throw new RestException(500, 'Error when delete Survey : '.$this->opensurveysondage->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Survey deleted'
            )
        );
    }

    /**
     * Validate a survey
     *
     * @param   string  $id         Survey ID
     * @param   int     $notrigger  1=Does not execute triggers, 0= execute triggers
     *
     * @url POST {id}/validate
     * @return  Object
     * @throws RestException
     */
    public function validate($id, $notrigger = 0)
    {
        if (!DolibarrApiAccess::$user->hasRight('opensurvey', 'write')) {
            throw new RestException(403, "Insufficient rights");
        }
        
        $result = $this->opensurveysondage->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Survey not found');
        }

        if (!DolibarrApi::_checkAccessToResource('opensurveysondage', $this->opensurveysondage->id_sondage)) {
            throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->opensurveysondage->status = Opensurveysondage::STATUS_VALIDATED;
        $result = $this->opensurveysondage->update(DolibarrApiAccess::$user, $notrigger);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already validated');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when validating survey: '.$this->opensurveysondage->error);
        }

        return $this->_cleanObjectDatas($this->opensurveysondage);
    }

    /**
 * Close a survey
 *
 * @param   string  $id         Survey ID
 * @param   array   $request_data   Request data (optional)
 *
 * @url POST {id}/close
 * @return  Object
 * @throws RestException
 */
public function close($id, $request_data = null)
{
    $notrigger = 0;
    
    // Si des données sont fournies dans le body
    if ($request_data && isset($request_data['notrigger'])) {
        $notrigger = (int)$request_data['notrigger'];
    }
    
    if (!DolibarrApiAccess::$user->hasRight('opensurvey', 'write')) {
        throw new RestException(403, "Insufficient rights");
    }
    
    $result = $this->opensurveysondage->fetch($id);
    if (!$result) {
        throw new RestException(404, 'Survey not found');
    }

    if (!DolibarrApi::_checkAccessToResource('opensurveysondage', $this->opensurveysondage->id_sondage)) {
        throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
    }

    $this->opensurveysondage->status = Opensurveysondage::STATUS_CLOSED;
    $result = $this->opensurveysondage->update(DolibarrApiAccess::$user, $notrigger);
    if ($result == 0) {
        throw new RestException(304, 'Error nothing done. May be object is already closed');
    }
    if ($result < 0) {
        throw new RestException(500, 'Error when closing survey: '.$this->opensurveysondage->error);
    }

    return $this->_cleanObjectDatas($this->opensurveysondage);
}

    /**
     * Get survey comments
     *
     * @param   string  $id     Survey ID
     * @return  array           Array of comments
     *
     * @url GET {id}/comments
     * @throws RestException
     */
    public function getComments($id)
    {
        if (!DolibarrApiAccess::$user->hasRight('opensurvey', 'read')) {
            throw new RestException(403);
        }

        $result = $this->opensurveysondage->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Survey not found');
        }

        if (!DolibarrApi::_checkAccessToResource('opensurveysondage', $this->opensurveysondage->id_sondage)) {
            throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $comments = $this->opensurveysondage->getComments();
        
        // Clean comment objects
        $cleanedComments = array();
        foreach ($comments as $comment) {
            $cleanedComments[] = $this->_cleanCommentDatas($comment);
        }

        return $cleanedComments;
    }

   /**
 * Add a comment to survey
 *
 * @param   string  $id             Survey ID
 * @param   string  $comment        Comment content
 * @param   string  $comment_user   Comment author (optional, defaults to current user)
 *
 * @url POST {id}/comments
 * @return  array
 * @throws RestException
 */
public function addComment($id, $comment, $comment_user = '')
{
    if (!DolibarrApiAccess::$user->hasRight('opensurvey', 'write')) {
        throw new RestException(403);
    }

    $result = $this->opensurveysondage->fetch($id);
    if (!$result) {
        throw new RestException(404, 'Survey not found');
    }

    if (!DolibarrApi::_checkAccessToResource('opensurveysondage', $this->opensurveysondage->id_sondage)) {
        throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
    }

    if (empty($comment_user)) {
        $comment_user = DolibarrApiAccess::$user->login;
    }

    if (!method_exists($this->opensurveysondage, 'addComment')) {
        if (method_exists($this->opensurveysondage, 'add_note')) {
            $result = $this->opensurveysondage->add_note($comment, DolibarrApiAccess::$user->id);
            if ($result > 0) {
                return array(
                    'success' => array(
                        'code' => 200,
                        'message' => 'Comment added successfully using add_note method'
                    )
                );
            } else {
                throw new RestException(500, 'Error adding comment using add_note: ' . $this->opensurveysondage->error);
            }
        }
        
        return $this->_addCommentManually($id, $comment, $comment_user);
    }

    // Ajouter l'adresse IP de l'utilisateur
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $result = $this->opensurveysondage->addComment($comment, $comment_user, $user_ip);
    if ($result) {
        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Comment added successfully'
            )
        );
    } else {
        // Ajouter plus d'informations sur l'erreur
        $errorMsg = 'Error adding comment';
        if (!empty($this->opensurveysondage->error)) {
            $errorMsg .= ': ' . $this->opensurveysondage->error;
        }
        if (!empty($this->opensurveysondage->errors)) {
            $errorMsg .= ' - ' . implode(', ', $this->opensurveysondage->errors);
        }
        throw new RestException(500, $errorMsg);
    }
}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
    /**
     * Clean sensible object datas
     *
     * @param   Object  $object     Object to clean
     * @return  Object              Object with cleaned properties
     */
    protected function _cleanObjectDatas($object)
    {
        // phpcs:enable
        $object = parent::_cleanObjectDatas($object);

        unset($object->db);
        unset($object->error);
        unset($object->errors);
        unset($object->date_m);
        unset($object->tms);

        return $object;
    }

    /**
     * Clean comment datas
     *
     * @param   Object  $comment    Comment to clean
     * @return  Object              Cleaned comment
     */
    protected function _cleanCommentDatas($comment)
    {
        // Keep only necessary fields
        $cleaned = new stdClass();
        $cleaned->id_comment = $comment->id_comment;
        $cleaned->usercomment = $comment->usercomment;
        $cleaned->comment = $comment->comment;
        
        return $cleaned;
    }

    /**
     * Validate fields before create or update object
     *
     * @param   array   $data   Array with data to verify
     * @return  array
     * @throws  RestException
     */
    private function _validate($data)
    {
        $survey = array();
        foreach (Opensurveysondages::$FIELDS as $field)  {
            if (!isset($data[$field])) {
                throw new RestException(400, "$field field missing");
            }
            $survey[$field] = $data[$field];
        }
        return $survey;
    }
}