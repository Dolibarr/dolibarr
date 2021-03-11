<?php
/* Copyright (C) 2014-2018 Regis Houssin <regis.houssin@inodbox.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
global $form;
?>

<!-- START PHP TEMPLATE ADMIN CACHES -->
<div class="table-border centpercent">
	<div class="table-border-row <?php echo $var?"pair":"impair"; ?>">
		<div class="tagtd padding-left5 seventy-percent" align="left"><b><?php echo $langs->trans("MulticompanySession"); ?></b><br><i><?php echo $langs->trans('MulticompanySessionDescription'); ?></i><br><br></div>
		<div class="tagtd valign-middle text-align-left">
			<div class="float-left"></div>
		</div>
		<div class="tagtd valign-middle button-align-right">
		<?php
		$input = array(
			'del' => array('MULTICOMPANY_MEMCACHED_ENABLED','MULTICOMPANY_SHMOP_ENABLED'),
			'disabled' => array('MULTICOMPANY_MEMCACHED_SERVER')
		);
		echo ajax_mcconstantonoff('MULTICOMPANY_SESSION_ENABLED', $input, 0);
		?>
		</div>
	</div>
</div>
<?php $var=!$var; ?>
<div class="table-border centpercent">
	<div class="table-border-row <?php echo $var?"pair":"impair"; ?>">
		<div class="tagtd padding-left5 seventy-percent" align="left"><b><?php echo $langs->trans("MulticompanyMemcached"); ?></b><br><i><?php echo $langs->trans('MulticompanyMemcachedDescription'); ?></i><br><br></div>
		<div class="tagtd valign-middle text-align-left">
			<div class="float-left">
				<?php
				if (class_exists("Memcached") || class_exists("Memcache"))
				{
					if (class_exists("Memcached")) $m=new Memcached();
					elseif (class_exists("Memcache")) $m=new Memcache();

					$serveraddress = (!empty($conf->global->MULTICOMPANY_MEMCACHED_SERVER)?$conf->global->MULTICOMPANY_MEMCACHED_SERVER:(!empty($conf->global->MEMCACHED_SERVER)?$conf->global->MEMCACHED_SERVER:'127.0.0.1:11211'));

					$tmparray=explode(':',$serveraddress);
					$server=$tmparray[0];
					$port=$tmparray[1]?$tmparray[1]:11211;

					$result=$m->addServer($server, $port);
					$arraycache=$m->getStats();

					if (is_array($arraycache))
						echo $form->textwithtooltip('',$langs->trans("MemcachedServerIsReady"),2,1,img_picto('','tick'),'',3);
					else
						echo $form->textwithtooltip('',$langs->trans("MemcachedServerIsNotReady"),2,1,img_warning(''),'',3);

					echo ' <input size="40" type="text" id="MULTICOMPANY_MEMCACHED_SERVER" name="MULTICOMPANY_MEMCACHED_SERVER" value="'.$serveraddress.'"'.(empty($conf->global->MULTICOMPANY_MEMCACHED_ENABLED) ? ' disabled="disabled"' : '').' />';
				}
				else
					echo img_warning($langs->trans("MulticompanyMemcachedUnavailable")).' '.$langs->trans("MulticompanyMemcachedUnavailable");
				?>
			</div>
		</div>
		<div class="tagtd valign-middle button-align-right">
		<?php
		if (class_exists("Memcached") || class_exists("Memcache"))
		{
			$input = array(
					'del' => array('MULTICOMPANY_SHMOP_ENABLED','MULTICOMPANY_SESSION_ENABLED'),
					'disabledenabled' => array('MULTICOMPANY_MEMCACHED_SERVER')
			);
			echo ajax_mcconstantonoff('MULTICOMPANY_MEMCACHED_ENABLED', $input, 0);
		}
		else
			echo '<span>'.img_picto($langs->trans("Disabled"),'switch_off', 'class="button-not-allowed"').'</span>';
		?>
		</div>
	</div>
</div>
<?php $var=!$var; ?>
<div class="table-border centpercent">
	<div class="table-border-row <?php echo $var?"pair":"impair"; ?>">
		<div class="tagtd padding-left5 seventy-percent" align="left"><b><?php echo $langs->trans("MulticompanyShmop"); ?></b><br><i><?php echo $langs->trans('MulticompanyShmopDescription'); ?></i><br><br></div>
		<div class="tagtd valign-middle text-align-left">
			<div class="float-left">
				<?php
				if (function_exists("shmop_read"))
					echo img_picto($langs->trans("MulticompanyShmopAvailable"),'tick').' '.$langs->trans("MulticompanyShmopAvailable");
				else
					echo img_warning($langs->trans("MulticompanyShmopUnavailable")).' '.$langs->trans("MulticompanyShmopUnavailable");
				?>
			</div>
		</div>
		<div class="tagtd valign-middle button-align-right">
		<?php
		if (function_exists("shmop_read"))
		{
			$input = array(
					'del' => array('MULTICOMPANY_MEMCACHED_ENABLED','MULTICOMPANY_SESSION_ENABLED'),
					'disabled' => array('MULTICOMPANY_MEMCACHED_SERVER')
			);
			echo ajax_mcconstantonoff('MULTICOMPANY_SHMOP_ENABLED', $input, 0);
		}
		else
			echo '<span>'.img_picto($langs->trans("Disabled"),'switch_off', 'class="button-not-allowed"').'</span>';
		?>
		</div>
	</div>
</div>
<!-- END PHP TEMPLATE ADMIN CACHES -->