<?php
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
class MyUser extends User
{
    function getSalaryTotalThisYear()
    {
        $year = date('Y');
        //print "Calculating salary total for user $this->id for year $year<br>";
        $sql = "SELECT SUM(amount) as total 
                FROM ".MAIN_DB_PREFIX."salary 
                WHERE fk_user=".$this->id."
                AND datesp >= '".$year."-01-01'
                AND datesp <= '".$year."-12-31'";

        $resql = $this->db->query($sql);
        $result = -1;
        if ($resql){
            $row = $this->db->fetch_row($resql);
            $result = $row[0];
            $this->db->free($resql);
        } else {
			dol_print_error($this->db);
		}
        return $result;
    }
}
