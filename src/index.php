<?php namespace AssemblyCompiler;

require_once 'consoleFunctions.php';
require_once 'compiler.php';

//get the commandline arguments
/** @noinspection PhpUndefinedVariableInspection */
$arguments = $argv;

unset($arguments[0]);//first one is location of script

//set the default values
$verboseLevel = 0;
$outPath = null;
$filePath = null;

foreach ($arguments as $argument) {
    if (!preg_match('/^--(\w+)=?(.*)?$/', $argument, $argumentParsed)) {
        echoConsole('unknown argument "'.$argument.'"');
        showHelp();
    }
    switch ($argumentParsed[1]) {
        case 'file': {
            $filePath = $argumentParsed[2];
            break;
        }
        case 'out': {
            $outPath = $argumentParsed[2];
            break;
        }
        case 'verbose': {
            $verboseLevel = (int) $argumentParsed[2];
            break;
        }
        case 'help': {
            showHelp();
            break;
        }
        default: {
            echoConsole('unknown argument "'.$argument.'"');
            showHelp();
            break;
        }
    }
}

//some checks
if ($filePath === null) {
    showHelp();
    die;
}

if (!file_exists($filePath)) {
    echoConsole('file "'.$filePath.'" not found');
    die;
}

if ($outPath === null) {
    $info = pathinfo($filePath);
    $outPath = $info['dirname'].'/'.$info['filename'].'.asm';
}

//get the file
$file = file_get_contents($filePath);

$compiler = new Compiler();
$compiler->debug = ($verboseLevel > 0);
$compiler->maxVariables = 5;
$compiler->loadCode($file);
$compiled = $compiler->compile();
echoConsole($compiled, 2);
file_put_contents($outPath, $compiled);

//compile to code the processor understands
echoConsole(shell_exec('Java -jar "./Assembler9.jar" "'.$outPath.'"'), 1);

echoConsole('Succesfully compiled.');