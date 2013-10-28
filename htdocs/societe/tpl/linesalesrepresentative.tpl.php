<?php
        // Sale representative
        print '<tr><td>';
        print '<table width="100%" class="nobordernopadding"><tr><td>';
        print $langs->trans('SalesRepresentatives');
        print '<td><td align="right">';
        if ($user->rights->societe->creer && $user->rights->societe->client->voir)
        print '<a href="'.DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$object->id.'">'.img_edit().'</a>';
        else
        print '&nbsp;';
        print '</td></tr></table>';
        print '</td>';
        print '<td colspan="3">';

        $listsalesrepresentatives=$object->getSalesRepresentatives($user);
        $nbofsalesrepresentative=count($listsalesrepresentatives);
        if ($nbofsalesrepresentative > 3)   // We print only number
        {
            print '<a href="'.DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$object->id.'">';
            print $nbofsalesrepresentative;
            print '</a>';
        }
        else if ($nbofsalesrepresentative > 0)
        {
            $userstatic=new User($db);
            $i=0;
            foreach($listsalesrepresentatives as $val)
            {
                $userstatic->id=$val['id'];
                $userstatic->lastname=$val['lastname'];
                $userstatic->firstname=$val['firstname'];
                print $userstatic->getNomUrl(1);
                $i++;
                if ($i < $nbofsalesrepresentative) print ', ';
            }
        }
        else print $langs->trans("NoSalesRepresentativeAffected");
        print '</td></tr>';
?>