<?php
/**
Common code for different parts of tasks tab.
**/

class TasksTabCommon {
	public static function getViewButtons(object $object, object $langs, string $selected, string $extraCssClass=''): string {
		$buttons = [
			['title'=>'ViewList', 'css'=>'fa fa-bars', 'url'=>'/projet/tasks.php?id='.$object->id],
			['title'=>'ViewGantt', 'css'=>'fa fa-stream', 'url'=>'/projet/ganttview.php?id='.$object->id.'&withproject=1']
		];
		$links = '';
		foreach ($buttons as $button) {
			$morecss = 'reposition';
			if ($button['title'] == $selected) $morecss .= ' btnTitleSelected';
			$links .= dolGetButtonTitle($langs->trans($button['title']), '',
				$button['css']." imgforviewmode $extraCssClass",
				DOL_URL_ROOT.$button['url'], '', 1, ['morecss'=>$morecss]);
		}

		return $links;
	}
}
