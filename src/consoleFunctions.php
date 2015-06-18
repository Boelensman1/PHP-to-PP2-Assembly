<?php namespace AssemblyCompiler;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Shows the help function
 *
 * @return void
 */
function showHelp()
{
	echoConsole("usage:compiler.phar [OPTION] --file=source_file\n
        \nYou have the following options:
        --out=output_file\t\tThe location of the output file\n
        --verbose=verbose_level\t\tVerbose level\n
        --assemble\t\t\tAssemble the file after compiling\n
        --help\t\t\t\tShow help\n
        --comments\t\t\tAdd comments to the resulting assembly
        \n");
}

/**
 * Outputs something to the console depending on the verbose level.
 *
 * @param string $input    What to output to the console
 * @param integer $verbose The verbose level of the message to output
 *
 * @return void
 */
function echoConsole($input, $verbose = 0)
{
    global $verboseLevel;
    if ($verbose <= $verboseLevel) {
        echo $input."\n";
    }
}
