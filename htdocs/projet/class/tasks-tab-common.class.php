<?php
/**
Common code for different parts of tasks tab.
**/

class TasksTabCommon
{
	/**
	 *  Get buttons for switching task list view.
	 *
	 *  @param      object		$object          Project object.
	 *  @param      object		$langs           Translation object.
	 *  @param      string		$selected        Title of selected view.
	 *  @param      string		$extraCssClass   Extra CSS classes to include with icon.
	 *  @return string HTML buttons.
	 */
	public static function getViewButtons(object $object, object $langs, string $selected, string $extraCssClass = ''): string
	{
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
