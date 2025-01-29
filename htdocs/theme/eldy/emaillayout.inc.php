<?php
if (!defined('ISLOADEDBYSTEELSHEET')) {
	die('Must be call by steelsheet');
}
?>
/* IDE Hack <style type="text/css"> */

.template-container {
	display: flex;
	justify-content: space-between;
	padding: 10px;
	background: #f5f5f5;
	border: 1px solid #d3d3d3;
	border-radius: 5px;
	margin-bottom: 15px;
  }

  .template-option {
	text-align: center;
	padding: 10px;
	margin: 0 5px;
	background: #e9e9e9;
	border: 1px solid #ccc;
	border-radius: 5px;
	cursor: pointer;
	height: 60px;
	width: 60px;
	display: inline-block;
	align-items: center;
	justify-content: center;
  }

  .template-option:hover {
	font-weight: bold;
	background: var(--butactionbg);
	color: var(--textbutaction);
	border-radius: 8px;
	border-collapse: collapse;
  }

  .template-option[data-template="ai"] {
	background: #c5f7c5;
  }

  .template-option[data-template="ai"]:hover {
	font-weight: bold;
	background: var(--butactionbg);
	color: var(--textbutaction);
	border-radius: 8px;
	border-collapse: collapse;
	border: none;
  }

  .template-option.selected {
	font-weight: bold;
	background: var(--butactionbg);
	color: var(--textbutaction);
	border-radius: 8px;
	border-collapse: collapse;
	border: none;
}

  #template-selector {
	/* width: 100%;
	max-width: 80%; */
	height: auto;
	padding: 10px;
	border: 1px solid #d3d3d3;
	border-radius: 5px;
	margin-bottom: 10px;
	margin-top: 10px;
	width: fit-content;
  }

  .template-option[data-template="ai"] i {
	font-size: 42px;
	display: block;
	width: 80%;
	max-height: 80px;
	margin: 0 5px;
	padding-top: 5px;
	border-radius: 5px;

}

.template-option[data-template="ai"] span {
  padding-top: 30px;
  font-size: 14px;

}

.template-option-text {
  padding-top: 3px;
  font-size: 14px;
}

#ai_input {
  display: none;
}

.template-option img {
  display: block;
  width: 80%;
  max-height: 80px;
  margin: 0 5px;
  padding-top: 5px;
  border-radius: 7px;
}
