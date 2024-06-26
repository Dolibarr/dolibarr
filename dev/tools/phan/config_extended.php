<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 */

// Load default configuration (with many exclusions)
//
$config = include __DIR__.DIRECTORY_SEPARATOR."config.php";

$config['plugins'] = [
		__DIR__.'/plugins/NoVarDumpPlugin.php',
		__DIR__.'/plugins/ParamMatchRegexPlugin.php',
		'DeprecateAliasPlugin',
		//'EmptyMethodAndFunctionPlugin',
		'InvalidVariableIssetPlugin',
		//'MoreSpecificElementTypePlugin',
		'NoAssertPlugin',
		'NotFullyQualifiedUsagePlugin',
		'PHPDocRedundantPlugin',
		'PHPUnitNotDeadCodePlugin',
		//'PossiblyStaticMethodPlugin',
		'PreferNamespaceUsePlugin',
		'PrintfCheckerPlugin',
		'RedundantAssignmentPlugin',

		'ConstantVariablePlugin', // Warns about values that are actually constant
		//'HasPHPDocPlugin', // Requires PHPDoc
		// 'InlineHTMLPlugin', // html in PHP file, or at end of file
		'NonBoolBranchPlugin', // Requires test on bool, nont on ints
		'NonBoolInLogicalArithPlugin',
		'NumericalComparisonPlugin',
		// 'PHPDocToRealTypesPlugin',  // Report/Add types to function definitions
		'PHPDocInWrongCommentPlugin', // Missing /** (/* was used)
		//'ShortArrayPlugin', // Checks that [] is used
		//'StrictLiteralComparisonPlugin',
		'UnknownClassElementAccessPlugin',
		'UnknownElementTypePlugin',
		'WhitespacePlugin',
		//'RemoveDebugStatementPlugin', // Reports echo, print, ...
		'SimplifyExpressionPlugin',
		//'StrictComparisonPlugin', // Expects ===
		'SuspiciousParamOrderPlugin',
		'UnsafeCodePlugin',
		//'UnusedSuppressionPlugin',

		'AlwaysReturnPlugin',
		//'DollarDollarPlugin',
		'DuplicateArrayKeyPlugin',
		'DuplicateExpressionPlugin',
		'PregRegexCheckerPlugin',
		'PrintfCheckerPlugin',
		'SleepCheckerPlugin',
		// Checks for syntactically unreachable statements in
		// the global scope or function bodies.
		'UnreachableCodePlugin',
		'UseReturnValuePlugin',
		'EmptyStatementListPlugin',
		'LoopVariableReusePlugin',
	];

// Add any issue types (such as 'PhanUndeclaredMethod')
// here to inhibit them from being reported
$config['suppress_issue_types'] = [
		// Dolibarr uses a lot of internal deprecated stuff, not reporting
		'PhanDeprecatedProperty',

		'PhanCompatibleNegativeStringOffset',	// return false positive
		'PhanPluginConstantVariableBool',		// a lot of false positive, in most cases, we want to keep the code as it is
		'PhanTypeArraySuspiciousNullable',		// this option costs more time to be supported than it solves time
		'PhanTypeInvalidDimOffset',				// this option costs more time to be supported than it solves time
		'PhanTypeObjectUnsetDeclaredProperty',
		'PhanTypePossiblyInvalidDimOffset',		// a lot of false positive, in most cases, we want to keep the code as it is
		'PhanPluginUnknownArrayFunctionReturnType',	// a lot of false positive, in most cases, we want to keep the code as it is

		'PhanPluginWhitespaceTab',		// Dolibarr used tabs
		'PhanPluginCanUsePHP71Void',	// Dolibarr is maintaining 7.0 compatibility
		'PhanPluginShortArray',			// Dolibarr uses array()
		'PhanPluginShortArrayList',		// Dolibarr uses array()
		// Fixers From PHPDocToRealTypesPlugin:
		'PhanPluginCanUseParamType',			// Fixer - Report/Add types in the function definition (function abc(string $var) (adds string)
		'PhanPluginCanUseReturnType',			// Fixer - Report/Add return types in the function definition (function abc(string $var) (adds string)
		'PhanPluginCanUseNullableParamType',	// Fixer - Report/Add nullable parameter types in the function definition
		'PhanPluginCanUseNullableReturnType',	// Fixer - Report/Add nullable return types in the function definition

		'PhanPluginNonBoolBranch',			// Not essential - 31240+ occurrences
		'PhanPluginNumericalComparison',	// Not essential - 19870+ occurrences
		'PhanTypeMismatchArgument',			// Not essential - 12300+ occurrences
		'PhanPluginNonBoolInLogicalArith',	// Not essential - 11040+ occurrences
		'PhanPluginConstantVariableScalar',	// Not essential - 5180+ occurrences
		'PhanPluginDuplicateAdjacentStatement',
		'PhanPluginDuplicateConditionalTernaryDuplication',		// 2750+ occurrences
		'PhanPluginDuplicateConditionalNullCoalescing',	// Not essential - 990+ occurrences
		'PhanPluginRedundantAssignmentInGlobalScope',	// Not essential, a lot of false warning
		'PhanPluginRedundantAssignment',				// Not essential, useless
		'PhanPluginDuplicateCatchStatementBody',  // Requires PHP7.1 - 50+ occurrences
];

return $config;
