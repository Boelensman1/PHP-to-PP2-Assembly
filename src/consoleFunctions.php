<?php namespace AssemblyCompiler;

/**
 * Shows the help function
 *
 * @return void
 */
function showHelp()
{
    echoConsole("usage:compiler.phar --file=source_file [--out=output_file] [--verbose=verbose_level]\n");
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