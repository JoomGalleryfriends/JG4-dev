<?php
$finder = PhpCsFixer\Finder::create()
  ->in(__DIR__)
  ->name('*.php');

$config = new PhpCsFixer\Config();

return $config->setRules([
  // Enforce LF line endings by enabling the rule
  'line_ending' => true,

  // Use spaces (no tabs) for indentation
  'indentation_type' => true,

  // Braces on a new line for functions and control structures
  'braces' => [
      'position_after_functions_and_oop_constructs' => 'next',
      'position_after_control_structures' => 'next',
  ],

  // Align the assignment operator (=)
  'binary_operator_spaces' => [
      'operators' => [ '=' => 'align' ],
  ],

  // Remove spaces around the concatenation operator (.)
  'concat_space' => ['spacing' => 'none'],

  // No extra spaces inside parentheses
  'no_spaces_inside_parenthesis' => true,

  // Use single quotes for strings when possible
  'single_quote' => true,

  // Remove trailing whitespace at the end of lines
  'no_trailing_whitespace' => true,

  // Order use statements alphabetically
  'ordered_imports' => ['sort_algorithm' => 'length'],

  // Ensure PHP constants like true, false and null are lower-case
  'constant_case' => true,

  // Fix PHPDoc indentation
  'phpdoc_indent' => true,

  // Ensure function declarations have correct spacing (no space between function name and parenthesis)
  'function_declaration' => true,

  // Ensure method arguments are spaced correctly on multiline declarations
  'method_argument_space' => [
      'on_multiline' => 'ensure_fully_multiline',
  ],

  // Convert hash comments (#) to double-slash (//)
  'single_line_comment_style' => ['comment_types' => ['hash']],

  // Remove any extra blank lines
  'no_extra_blank_lines' => true,
])->setFinder($finder);
