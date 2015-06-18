<?php namespace AssemblyCompiler;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file contains the compiler class.
 *
 * The compiler class is the class doing all the real work. This is what compiles the code.
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2015 Wigger Boelens
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Wigger Boelens <wigger.boelens@gmail.com>
 * @copyright  2015 Wigger Boelens
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    v0.1
 */

use stdClass;

// {{{ constants
define('IOAREA', -16);
define('DSPSEG', 8);
define('DSPDIG', 9);
define('TIMER', 13);
define('INPUT', 7);
define('OUTPUT', 11);
define('OUTPUT2', 10);
define('ADCONVS', 6);
// }}}

/**
 * The class that handles the actual compiling of the code.
 *
 * @author Wigger Boelens <wigger.boelens@gmail.com>
 */
class Compiler
{
    // {{{ properties

    /**
     * The input code
     *
     * This variable will contain the input code.
     *
     * @var array
     */
    public $code = [];

    /**
     * Running in debug mode
     *
     * Whether we are running in debug mode.
     * Possible values are true/false. Defaults to false.
     *
     * @var boolean
     */
    public $debug = false;

    /**
     * Insert comments
     *
     * Whether we should insert comments into the assembly.
     * Possible values are true/false. Defaults to true.
     *
     * @var boolean
     */
    public $insertComments = true;

    /**
     * Maximum variables
     *
     * How many variables are allowed to be used in the input code.
     *
     * @var int
     */
    public $maxVariables = 4;

    /**
     * Data variables
     *
     * The variables defined in the data segment.
     *
     * @var    array
     * @access private
     */
    private $_data = [];

    /**
     * Functions that will move  up
     *
     * Is defined in the compiler segment.
     *
     * @var    array
     * @access private
     */
    private $_moveFunction = [];


    /**
     * Uncompiled functions
     *
     * All functions in the input file.
     *
     * @var    array
     * @access private
     */
    private $_functions = [];

    /**
     * Compiled functions
     *
     * The functions in the input file that have been compiled.
     *
     * @var    array
     * @access private
     */
    private $_functionsCompiled = [];

    /**
     * Functions to be compiled
     *
     * The functions that have not yet been compiled.
     *
     * @var    array
     * @access private
     */
    private $_functionsToCompile = [];

    /**
     * Variables
     *
     * The variables and the registers that they represent
     *
     * @var    array
     * @access private
     */
    private $_variables = [];

    /**
     * The conditionals we are in
     *
     * All the conditionals we are currently in.
     * $_inConditional[0] is the outermost one,
     * $_inConditional[1] the one inside that one etc.
     *
     * @var    array
     * @access private
     */
    private $_inConditional = [];

    /**
     * Conditional just closed
     *
     * If a conditional has just been closed.
     *
     * @var    bool
     * @access private
     */
    private $_conditionals = [];

    /**
     * The conditionals
     *
     * A list of all the conditionals, used to make the comments
     *
     * @var    stdClass
     * @access private
     */
    private $_conditionalJustClosed = false;

    /**
     * Line number
     *
     * For each function keeps track of the line numbers
     *
     * @var    array
     * @access private
     */
    private $_lineNumber = [];

    /**
     * Current function name
     *
     * The name of the function that we are currently compiling.
     *
     * @var    string
     * @access private
     */
    private $_functionName;

    /**
     * Current line
     *
     * The line we are currently compiling.
     *
     * @var    string
     * @access private
     */
    private $_line;

    /**
     * Used default functions
     *
     * Contains the code of the used default functions
     *
     * @var    string
     * @access private
     */
    private $_defaultFunctions = [];

    /**
     * Which default functions are used.
     *
     * Keeps track of whether the default function to wait is used,
     * so we know to insert it when compiling.
     *
     * @var    array
     * @access private
     */
    private $_useFunction = [
        'sleep' => false,
        'pressed' => false,
        'pow' => false,
        'display' => false
    ];

    /**
     * Load the code. This function does some first processing
     * and saves the code in the object.
     *
     * @param string $code The input code
     *
     * @return bool Success
     */
    public function loadCode($code)
    {
        //remove all comments
        $isComment = false;
        $compilerSegment=false;
        $codeSegment = false;
        $dataSegment = false;
        //split by _line
        foreach (preg_split("/((\r?\n)|(\r\n?)|;)/", $code) as $line) {
            if (empty($line)) {
                //there are a lot of empty lines because we split on ;
                continue;
            }
            $line = trim($line);//trim the line.

            //check if compilersegment starts
            if ($compilerSegment==false && $dataSegment == false && $codeSegment == false) {
                if ($line == '//**COMPILER**') {
                    $compilerSegment = true;
                }
                continue;
            }

            //check if datasegment starts
            if ($compilerSegment==false && $dataSegment == false && $codeSegment == false) {
                if ($line == '//**DATA**') {
                    $dataSegment = true;
                }
                continue;
            }

            //if we are currently in a block comment,
            //we only need to check if we get out of it
            if ($isComment == true) {
                if ($line == '*/') {
                    $isComment = false;
                }
                continue;
            } else {
                if ($line == '/*' || $line == '/**') {
                    $isComment = true;
                    continue;
                }
                //check for comments, but not the code/data comment
                if (preg_match(
                        "/(^|[^\\\\])\\/\\//", $line, $matches, PREG_OFFSET_CAPTURE
                    ) && $line != '//**CODE**' && $line != '//**DATA**'
                ) {
                    $line = substr(
                        $line, 0, $matches[0][1] + 1
                    );
                    //copy all that is not a comment
                    if ($line == '/') {
                        continue;
                    }
                }
                //check for global, which we ignore
                if (substr($line, 0, 6) === 'global') {
                    continue;
                }
            }
            if (!empty($line)) {
                if ($compilerSegment) {
                    //check if the _data segment ends
                    if ($codeSegment == false) {
                        if ($line == '//**DATA**') {
                            $compilerSegment=false;
                            $dataSegment = true;//compilerSegment ends when dataSegment starts
                            continue;
                        }
                    }

                    //process it
                    $this->processCompiler($line);
                }
                if ($dataSegment) {
                    //check if the _data segment ends
                    if ($codeSegment == false) {
                        if ($line == '//**CODE**') {
                            $dataSegment = false;//datasegment ends when codesegment starts
                            $codeSegment = true;
                            continue;
                        }
                    }

                    //add it to the _data
                    $this->_data[] = $this->processData($line);
                }
                if ($codeSegment) {
                    //check for statements like $abc++
                    if (preg_match("/^\\$(.+)(\\+\\+|--)/", $line, $matches)) {
                        if ($matches[2] == '++') {
                            $line = '$' . $matches[1] . '+=1';
                        } else {
                            $line = '$' . $matches[1] . '-=1';
                        }
                    }
                    //add it to the code
                    $this->code[] = $line;
                }
            }
        }

        return (!empty($this->code));//return true if the code is not empty
    }

    /** Processes the lines of data-code
     *
     * @param string $line The input line
     *
     * @return string The processed line of data-code
     */
    private function processData($line)
    {
        //set the line number in case we get an error
        $this->_functionName = '@DATA';
        $this->_lineNumber['@DATA'] = 0;
        $return = $this->processLine($line);
        if ($return[0] !== 0) {
            //we should always get single lines out of _data
            $this->error('Unknown code in _data segment');
        }

        return $return[1];
    }

    /** Processes the lines of compiler-code
     *
     * @param string $line The input line
     *
     * @return string The processed line of compiler-code
     */
    private function processCompiler($line)
    {
        //set the line number in case we get an error
        $this->_functionName = '@COMPILER';
        $this->_lineNumber['@COMPILER'] = 0;

        //and do the compiler
        $this->processLine($line);
    }

    /**Subroutine to process a single _line
     *
     * @param string $line The _line to be compiled
     *
     * @return array First element is a status code, the next can be anything,
     *               but most often the compiled lines
     */
    private function processLine($line)
    {
        //$_lineNumber =$this->_lineNumber[$this->_functionName];
        $this->_line = $line;

        //check if we are dealing with a build in function
        if (preg_match(
            "/^\\b[^()]+\\((.*)\\)/", $line, $function, PREG_OFFSET_CAPTURE
        )) {
            return $this->processFunction($line, $function);
        }
        //if not lets see if we are dealing with a = statement
        if (preg_match("/(.*?)=(.*)/", $line, $variable, PREG_OFFSET_CAPTURE)) {
            return $this->processEqualStatement($variable);
        }
        //maybe its an else
        if (preg_match("/^}\\s*else\\s*{/", $line)) {
            if (count($this->_inConditional) === 0) {
                $this->error('else without an if');
            }
            $this->_inConditional[count($this->_inConditional) - 1]['type'] = 'else';

            return [3];
        }

        //maybe its something else
        switch ($line) {
            case 'return': {
                return [0, 'RTS'];
            }
            case 'returnt': {
                return [0, 'RTE'];
            }
            case '}': {
                $this->_conditionalJustClosed = end($this->_inConditional);
                $id = $this->_conditionalJustClosed['id'];
                //if ($this->_conditionalJustClosed['type'] == 'if') {
                $this->_conditionalJustClosed['name'] = 'return' . $id;

                return [2, 'BRA return' . $id];
                /*}
                if ($this->_conditionalJustClosed['type'] == 'else') {
                    $this->_conditionalJustClosed = str_replace('conditional', 'return', $this->_conditionalJustClosed);
                    return [2, ];
                }*/
                break;
            }
        }
        //its the closing of an if/else
        if ($line === '}') {
        }
        //we do not know how to handle this
        return [-1];
    }

    private function processFunction($line, $function)
    {
        $arguments = $function[1][0];//the arguments
        $function = substr(
            $function[0][0], 0, $function[1][1] - 1
        );//the actual function

        //get the arguments
        preg_match_all(
            "/([^,]+\\(.+?\\))|([^,]+)/", $arguments, $arguments
        );
        $arguments = $arguments[0];//the arguments
        //now lets make that _line!
        switch (trim($function)) {
            case 'define': {
                return [
                    0,
                    trim(trim($arguments[0]), "'\"") . ' EQU '
                    . $this->processArgument(trim($arguments[1]), "'\"")
                ];
            }
            case 'storeRam': {
                return [
                    0,
                    'STOR ' . $this->processArgument($arguments[0]) . ' ['
                    . $this->processArgument($arguments[1]) . ']'
                ];
            }
            case 'storeData': {
                //check if we have to add a register
                if (substr(trim($arguments[2]), 0, 1) == '$' && trim($this->processArgument($arguments[2])) != '0'
                ) {
                    return [
                        4,
                        'ADD ' . $this->processArgument($arguments[2]) . ' ' . trim($this->processArgument($arguments[1]),
                            '\''),
                        'STOR ' . $this->processArgument($arguments[0]) . ' [ GB + '
                        . $this->processArgument($arguments[2]) . ']',
                        'SUB ' . $this->processArgument($arguments[2]) . ' ' . trim($this->processArgument($arguments[1]),
                            '\''),
                    ];
                } else {
                    //its just a number
                    return [
                        0,
                        'STOR ' . $this->processArgument($arguments[0]) . ' [GB +' .
                        trim($this->processArgument($arguments[1]),
                            '\'') . ' + ' . $this->processArgument($arguments[2]) . ']'
                    ];
                }
            }
            case 'initVar'://only for _data segment
            {
                return [
                    0,
                    trim($this->processArgument($arguments[0]),
                        '\'') . ' DS ' . $this->processArgument($arguments[1])
                ];
            }
            case 'if': {
                //an if statement, we need to create a function for this.
                preg_match(
                    "/^if\\s*?\\((.+)(!=|==|>=|<=|<|>)(.+)\\)/", $line, $matches
                );
                switch ($matches[2]) {
                    case '!=': {
                        return [
                            1,
                            'CMP ' . $this->processArgument($matches[1],true) . ' '
                            . $this->processArgument($matches[3]),
                            'BNE ' . $this->getNextConditional('if')
                        ];
                    }
                    case '==': {
                        return [
                            1,
                            'CMP ' . $this->processArgument($matches[1],true) . ' '
                            . $this->processArgument($matches[3]),
                            'BEQ ' . $this->getNextConditional('if')
                        ];
                    }
                    case '<': {
                        return [
                            1,
                            'CMP ' . $this->processArgument($matches[1],true) . ' '
                            . $this->processArgument($matches[3]),
                            'BMI ' . $this->getNextConditional('if')
                        ];
                    }
                    case '>': {
                        return [
                            1,
                            'CMP ' . $this->processArgument($matches[1],true) . ' '
                            . $this->processArgument($matches[3]),
                            'BGT ' . $this->getNextConditional('if')
                        ];
                    }
                    case '>=': {
                        return [
                            1,
                            'CMP ' . $this->processArgument($matches[1],true) . ' '
                            . $this->processArgument($matches[3]),
                            'BGE ' . $this->getNextConditional('if')
                        ];
                    }
                    case '<=': {
                        return [
                            1,
                            'CMP ' . $this->processArgument($matches[1],true) . ' '
                            . $this->processArgument($matches[3]),
                            'BLE ' . $this->getNextConditional('if')
                        ];
                    }
                    default: {
                        $this->error('unknown if statement');
                    }
                }
                break;
            }

            case 'mod': {
                return [
                    0,
                    'MOD ' . $this->processArgument($arguments[1],true) . ' '
                    . $this->processArgument($arguments[0],true)
                ];
            }

            case 'unset': {
                //unset a variable
                foreach ($arguments as $argument)
                {
                    $this->unsetRegister($argument);
                }
                break;
            }



            case 'debug': {
                $comments='False';
                if ($this->insertComments===true)
                {
                    $comments='True';
                }
                $this->error(
                    'max variables is ' . $this->maxVariables."\n"
                    .'your current variables are: '.implode(', ',$this->_variables)."\n"
                    .'Insert comments: '.$comments
                );
                break;
            }

            case 'moveFunction':{
                $move['name']=trim(trim($arguments[0]), '\'"');
                $move['pos']=trim($arguments[1]);
                $this->_moveFunction[]=$move;
                unset($move);
                break;
            }

            case 'compile':{
                $this->_functionsToCompile[] = trim(trim($arguments[0]), '\'"');//add it to the to compile functions
                break;
            }

            case 'getInput': {
                switch (trim(trim($arguments[1]), "'\"")) {
                    case 'buttons': {
                        return [
                            4,
                            'PUSH R5',
                            'LOAD  R5  ' . IOAREA,
                            'LOAD ' . $this->processArgument($arguments[0]) . ' [R5 + ' . INPUT . ']',
                            'PULL R5'
                        ];
                    }
                    case 'analog': {
                        return [
                            4,
                            'PUSH R5',
                            'LOAD  R5  ' . IOAREA,
                            'LOAD ' . $this->processArgument($arguments[0]) . ' [R5 + ' . ADCONVS . ']',
                            'PULL R5'
                        ];
                    }
                    default: {
                        $this->error('unknown input type.');
                    }
                }
                break;
            }

            case 'display': {
                switch (trim(trim($arguments[1]), '"\'')) {
                    case 'display': {
                        $this->_useFunction['display'] = true;
                        $counter = str_repeat('0', 6 - $arguments[2]) . '1' . str_repeat(
                                '0', intval($arguments[2])
                            );//000001
                        return [
                            4,
                            'LOAD  R5 ' . $this->processArgument($arguments[0]),
                            'BRS _Hex7Seg',
                            'LOAD  R4  %' . $counter,
                            'STOR  R4  [R5+' . DSPDIG . ']'
                        ];
                        break;
                    }
                    case 'leds': {//the led lights
                        return [
                            4,
                            'PUSH R5',
                            'LOAD  R5  ' . IOAREA,
                            'STOR '.$this->processArgument($arguments[0]).' [R5+' . OUTPUT . ']',
                            'PULL R5'
                        ];
                        break;
                    }
                    case 'leds2': {
                        return [
                            4,
                            'PUSH R5',
                            'LOAD R5  ' . IOAREA,
                            'STOR '.$this->processArgument($arguments[0]).' [R5+' . OUTPUT2 . ']',
                            'PULL R5'
                        ];
                        break;
                    }
                    default: {
                        $this->error('unknown output type');
                        break;
                    }
                }
                break;
            }

            case 'sleep': {
                $this->_useFunction['sleep'] = true;

                return [
                    4,
                    'PUSH R5',
                    'LOAD  R5 ' . $this->processArgument($arguments[0]),
                    'BRS _timer',
                    'PULL R5'
                ];
            }

            case 'installCountdown': {
                $countdown = "LOAD  R0  " . trim(trim($arguments[0]), '\'"') . "
                       ADD  R0  R5
                      LOAD  R1  16
                      STOR  R0  [R1]

                      LOAD  R5  " . IOAREA . "

                      ; Set the timer to 0
                      LOAD  R0  0
                       SUB  R0  [R5+" . TIMER . "]
                      STOR  R0  [R5+" . TIMER . "]";
                $return = [4];
                $return = array_merge($return, explode("\n", $countdown));
                $this->_functionsToCompile[] = trim(trim($arguments[0]), '\'"');//add it to the to compile functions
                $this->_functions[trim(trim($arguments[0]), '\'"')]->isTimer = true;

                return $return;
            }

            case 'startCountdown': {
                return [0, 'SETI  8'];
            }
            case 'pushStack': {
                return [0, 'PUSH ' . $this->processArgument($arguments[0])];
            }
            case 'pullStack': {
                return [0, 'PULL ' . $this->processArgument($arguments[0])];
            }
            case 'setCountdown': {
                return [
                    4,
                    'PUSH R5',
                    'PUSH R4',
                    'LOAD R5 ' . IOAREA,
                    'LOAD  R4  0',
                    'SUB  R4  [R5+' . TIMER . ']',
                    'STOR  R4  [R5+' . TIMER . ']',
                    'LOAD R4 ' . $this->processArgument($arguments[0]),
                    'STOR R4 [R5+' . TIMER . ']',
                    'PULL R4',
                    'PULL R5'
                ];
            }
            case 'stackPush': {
                return [0, 'PUSH ' . $this->processArgument($arguments[0])];
            }
            case 'stackPull': {
                return [0, 'PULL ' . $this->processArgument($arguments[0])];
            }
            case 'branch': {
                return [0, 'BRA ' . trim(trim($arguments[0]), "'\"")];
            }
            default: {
                //error or another function
                if (isset($this->_functions[$function])) {
                    //okay, function exists, lets add it to the _functions we need to compile
                    if (!isset($this->_functionsCompiled[$function])
                        && !in_array($function, $this->_functionsToCompile)
                    ) {
                        $this->_functionsToCompile[] = $function;
                    }
                    //lets see if it has a return
                    if ($this->_functions[$function]->hasReturn === true) {
                        return [0, 'BRS ' . $function];
                    } else {
                        return [0, 'BRA ' . $function];
                    }
                }
                //unknown function
                $this->error('unknown function "' . $function . '"');
                break;
            }
        }
        return false;
    }

    /**Process an argument, for example $abc + 1 gets translated into R0 +1.
     *
     * @param string $argument  The argument to process
     * @param bool  $errorOnNew Whether to error when the register is not initialized.
     *
     * @return string The processed argument
     */
    private function processArgument($argument, $errorOnNew=false)
    {
        $argument = trim($argument);
        //lets see if we are dealing with a +, -, * etc.
        if (strpos($argument, '+') !== false) {
            //+
            $arguments = explode('+', $argument);
            if (empty($arguments[0]) or empty($arguments[1])) {
                return $argument;
            }
            $argument = '';
            foreach ($arguments as $arg) {
                $argument .= $this->processArgument($arg,$errorOnNew) . ' + ';
            }
            $argument = substr($argument, 0, -3);

            return $argument;
        }
        if (strpos($argument, '-') !== false) {
            //+
            $arguments = explode('-', $argument);
            if (empty($arguments[0]) or empty($arguments[1])) {
                return $argument;
            }
            $argument = '';
            foreach ($arguments as $arg) {
                $argument .= $this->processArgument($arg,$errorOnNew) . ' - ';
            }
            $argument = substr($argument, 0, -3);

            return $argument;
        }

        if (strpos($argument, '%') !== false) {
            $this->error('Unexpected %');
        }
        //check if variable
        if (substr($argument, 0, 1) === '$') {
            //get the variable
            $argument = $this->getRegister($argument,$errorOnNew);

            return $argument;
        }

        //nothing special
        return $argument;
    }

    /**Throws an error
     *
     * @param string $error The error
     * @param bool $less Whether to give less information, default false
     */
    private function error($error, $less = false)
    {
        if ($less == true) {
            die("!!\tERROR while compiling:\n!!\t$error.");
        }
        $funcname=$this->_functionName;

        if (count($this->_inConditional)>0)
        {
            $inCon=$this->_inConditional[count($this->_inConditional)-1];
            $funcname=$inCon['parent'].'('.$inCon['linestart'].') -> '.$funcname.'  ( '.
                $inCon['statement']
                .' )';
        }

        $message="!!\tERROR while compiling around _line #"
            . $this->_lineNumber[$this->_functionName]
            . " in function $funcname:\n!!\t" . $this->_line;
        $error=explode("\n",$error);
        foreach ($error as $errorLine)
        {
            $message.="\n!!\t$errorLine.";
        }
         //   . "\n!!\t$error.";
        die($message);
    }

    /** Gets the register who belongs to the given variable, creates a new one if none exists.
     *
     * @param string $variable   The variable to look for
     * @param bool   $errorOnNew Whether to error when the register is not initialized.
     *
     * @return string The register belonging to the variable.
     */
    private function getRegister($variable, $errorOnNew=false)
    {
        $variable = trim($variable);
        //check if we already have this variable
        if (in_array($variable, $this->_variables)) {
            return 'R' . array_search($variable, $this->_variables);
        } else {
            //if not, make a new one, if allowed
            if ($errorOnNew===true)
            {
                $this->error('Using uninitialized variable: '.$variable);
            }

            //ok, lets make the var
            for ($i=0;$i<$this->maxVariables;$i++)
            {
                if (!isset($this->_variables[$i]))
                {
                    $this->_variables[$i] = $variable;
                    return 'R' . $i;
                }

            }
            $this->error(
                'too many _variables, max is ' . $this->maxVariables."\n"
                .'your current variables are: '.implode(', ',$this->_variables)
            );
            }
        $this->error('Unknown error while getting register');

        return '';
    }

    /** Unsets a register, so a new one can be created.
     *
     * @param string $variable The variable to unset
     *
     * @return bool True, or errors out if it something went wrong.
     */
    private function unsetRegister($variable)
    {
        $variable = trim($variable);

        //check if we have this variable
        if (!in_array($variable, $this->_variables)) {
            $this->error('Unsetting unknown variable: '.$variable);
        }

        $register=array_search($variable, $this->_variables);
        unset($this->_variables[$register]);
        return true;
    }

    /** Create a new if/else function
     *
     * @param string $type Whether its a if or an else
     *
     * @return string the name of the new conditional.
     */
    private function getNextConditional($type)
    {
        $i = @end($this->_inConditional)['id'];//the last key as starting position
        if (!is_int($i)) {
            $i = -1;
        }
        while (true) {
            $i++;
            if (!isset($this->_functions['conditional' . $i])
                && !isset($this->_functions['return' . $i])
                && !isset($this->_functionsCompiled['conditional' . $i])
                && !isset($this->_functionsCompiled['return' . $i])
            ) {
                $index = count($this->_inConditional);
                $this->_inConditional[$index]['name'] = 'conditional' . $i;
                $this->_inConditional[$index]['id'] = $i;
                $this->_inConditional[$index]['type'] = $type;
                $this->_inConditional[$index]['parent'] = $this->_functionName;
                $this->_inConditional[$index]['statement'] = $this->_line;
                $this->_inConditional[$index]['linestart'] = $this->_lineNumber[$this->_functionName];

                $this->_functionsCompiled['conditional' . $i] = new stdClass();
                $this->_functionsCompiled['conditional' . $i]->code = [];
                $this->_functionsCompiled['conditional' . $i]->returns = [];
                $this->_functionsCompiled['conditional' . $i]->isConditional = true;
                $this->_functionsCompiled['conditional' . $i]->statement = $this->_line;

                $this->_lineNumber['conditional' . $i] = 0;

                return 'conditional' . $i;
            }
        }
        $this->error('Unknown error while getting conditional');

        return '';
    }


    /** Process a $abc=5 statement
     *
     * @param string $variable The variable that is =, for example $abc
     *
     * @return string the name of the new conditional.
     */
    private function processEqualStatement($variable)
    {
        $rest = trim($variable[2][0]);
        $variable = trim($variable[1][0]);
        $register = '';
        //lets see if we are dealing with a +/-
        switch (substr($variable, -1)) {
            case '+': {
                //lets see if we are dealing with a variable
                if (substr($variable, 0, 1) === '$') {
                    $variable = substr($variable, 0, -1);
                    //get the variable
                    $register = $this->getRegister($variable);

                    return [
                        0,
                        'ADD ' . $register . ' ' . $this->processArgument($rest)
                    ];
                }
                $this->error('Addition on a non-variable.');
                break;
            }
            case '-': {
                if (substr($variable, 0, 1) === '$') {
                    $variable = substr($variable, 0, -1);
                    //get the variable
                    $register = $this->getRegister($variable);

                    return [
                        0,
                        'SUB ' . $register . ' ' . $this->processArgument($rest)
                    ];
                }
                $this->error('Subtraction on a non-variable.');
                break;
            }
            case '/': {
                if (substr($variable, 0, 1) === '$') {
                    $variable = substr($variable, 0, -1);
                    //get the variable
                    $register = $this->getRegister($variable);

                    return [
                        0,
                        'DIV ' . $register . ' ' . $this->processArgument($rest)
                    ];
                }
                $this->error('Division on a non-variable.');
                break;
            }
            case '*': {
                if (substr($variable, 0, 1) === '$') {
                    $variable = substr($variable, 0, -1);
                    //get the variable
                    $register = $this->getRegister($variable);

                    return [
                        0,
                        'MULS ' . $register . ' ' . $this->processArgument($rest)
                    ];
                }
                $this->error('Multiplication on a non-variable.');
                break;
            }
        }
        //lets see if we are dealing with a variable
        if (substr($variable, 0, 1) === '$') {
            //get the variable
            $register = $this->getRegister($variable);
        }

        //okay lets now do something with the rest
        //lets see if there is a function
        if (preg_match(
            "/^\\b[^()]+\\((.*)\\)/", $rest, $function, PREG_OFFSET_CAPTURE
        )) {
            $arguments = $function[1][0];//the arguments
            $function = substr(
                $function[0][0], 0, $function[1][1] - 1
            );//the actual function
            //get the arguments
            preg_match_all(
                "/([^,]+\\(.+?\\))|([^,]+)/", $arguments, $arguments
            );
            $arguments = $arguments[0];//the arguments
            switch ($function) {
                case 'getRam'; {
                    return [
                        0,
                        'LOAD ' . $register . ' [' . $this->processArgument(
                            $arguments[0]
                        ) . ']'
                    ];
                }
                case 'getButtonPressed': {
                    $this->_useFunction['pressed'] = true;
                    $this->_useFunction['pow']=true;
                    return [
                        4,
                        'PUSH R3',
                        'LOAD R3 ' . $this->processArgument($arguments[0]),
                        'BRS _pressed',
                        'PULL R3',
                        'SUB SP 5',
                        'PULL '.$register,
                        'ADD SP 4',
                    ];
                }
                case 'getData'; {
                    //check if we have to add a register
                    if (substr(trim($arguments[1]), 0, 1) == '$') {
                        return [
                            4,
                            'ADD ' . $this->processArgument($arguments[1]) . ' ' . trim($this->processArgument($arguments[0]),
                                '\''),
                            'LOAD ' . $register . ' [ GB + '
                            . $this->processArgument($arguments[1]) . ']',
                            'SUB ' . $this->processArgument($arguments[1]) . ' ' . trim($this->processArgument($arguments[0]),
                                '\''),
                        ];
                    } else {
                        return [
                            0,
                            'LOAD ' . $register . ' [ GB + ' . trim($this->processArgument($arguments[0]),
                                '\'') . ' + ' . $this->processArgument($arguments[1]) . ' ]'
                        ];
                    }
                }
                case 'pow': {
                    $this->_useFunction['pow'] = true;

                    return [
                        4,
                        'PUSH R4',
                        'PUSH R5',
                        'LOAD R4 ' . $this->processArgument($arguments[1]),
                        'LOAD R5 ' . $this->processArgument($arguments[0]),
                        'BRS _pow',
                        'LOAD '.$register.' R5',
                        'PULL R5',
                        'PULL R4'
                    ];
                }
                default: {
                    $this->error('unknown function "' . $function . '"');
                }
            }
        }
        //if nothing else, its a simple store
        return [
            0,
            'LOAD ' . $register . ' ' . $this->processArgument($rest)
        ];
    }

    /**
     * Compiles the code loaded in.
     *
     * @return string The compiled code.
     */
    public function compile()
    {
        //lets start with reading all the _functions
        $functions = $this->getFunctions();
        $functionId = 0;//the function we are looking for
        $inFunction = false;
        $codeOutside = [];
        //now we have the _functions lets start with doing all the code outside of the _functions:
        foreach ($this->code as $lineNumber => $line) {
            if ($inFunction) {
                if ($lineNumber === $functions[$functionId]->lineNumberEnd) {
                    $inFunction = false;
                    $functionId++;
                }
                continue;
            }
            if ($lineNumber === $functions[$functionId]->lineNumberStart) {
                $inFunction = true;
                //lets add this function to the main array
                $this->_functions[$functions[$functionId]->name]
                    = $functions[$functionId];
                continue;
            }
            //if we get to here, we are definitely not inside of a function
            $codeOutside[] = $this->processLine(
                $line, $lineNumber, '__outside__'
            )[1];
        }

        //now lets do the _functions
        $this->_functionsToCompile[] = 'main';
        while (!empty($this->_functionsToCompile)) {
            $this->_compileFunction(array_pop($this->_functionsToCompile));
            if (count($this->_inConditional) != 0) {
                //we are in a conditional
                $this->error(
                    'Still in a conditional at the end of the function!'
                );
            }
        }

        return $this->makeCode($codeOutside);
    }

    /**Gets all the function defined in the source code
     *
     * @return array The _functions
     */
    private function getFunctions()
    {
        $i = -1;
        $functions = [];
        $brackets = 0;
        foreach ($this->code as $lineNumber => $line) {
            if (substr($line, 0, 8) == 'function') {
                $i++;
                $functions[$i] = new stdClass();
                $functions[$i]->name = trim(substr($line, 9, -2));
                $functions[$i]->lineNumberStart = $lineNumber;
                $functions[$i]->code = [];
                $functions[$i]->hasReturn = false;
                $functions[$i]->isTimer = false;
            } else {
                if ($line == '{'
                    || preg_match(
                        "/(if|else|function).*\\{/", $line
                    )
                ) {
                    //check for comments
                    $brackets++;
                }
            }
            if (substr($line, 0, 1) == '}') {
                $brackets--;
                if ($brackets == 0) {
                    $functions[$i]->lineNumberEnd = $lineNumber;
                }
            }
            if ($brackets > 0) {
                if (count($functions[$i]->code) == 0 && $line === '{') {
                    continue;
                }
                $functions[$i]->code[] = $line;
                if (trim($line) === 'return') {
                    $functions[$i]->hasReturn = true;
                }
            }
        }

        return $functions;
    }

    /**
     * Subroutine to compile a function, if the function is already compiled it
     * will skip it.
     *
     * @param string $functionName The name of the function to be compiled.
     */
    private function _compileFunction($functionName)
    {
        $this->_functionName = $functionName;
        //if we already have this function compiled. Lets skip it.
        if (isset($this->_functionsCompiled[$functionName])) {
            return;
        }
        if (!isset($this->_functions[$functionName])) {
            //unknown function!
            $this->error('unknown function "' . $functionName . '"', true);
        }
        //create the _variables
        $this->_functionsCompiled[$functionName] = new stdClass();
        $this->_functionsCompiled[$functionName]->code = [];
        $this->_functionsCompiled[$functionName]->returns = [];
        $this->_functionsCompiled[$functionName]->isConditional = false;

        $this->_inConditional = [];

        $this->_lineNumber[$functionName] = 0;
        foreach ($this->_functions[$functionName]->code as $line) {
            $lineTMP = $this->processLine($line);
            //check what happened with our _line
            switch ($lineTMP[0]) {
                case 0: //everything went OK, nothing special
                {
                    $i = count($this->_inConditional);
                    $this->insertCode($functionName, $lineTMP[1], $i, $line);
                    break;
                }
                case
                1://if statement
                {
                    $i = count($this->_inConditional) - 1; //to insert in the parent
                    $this->insertCode($functionName, $lineTMP[1], $i, $line);
                    $this->insertCode($functionName, $lineTMP[2], $i);
                    $this->_functionName = end($this->_inConditional)['name'];
                    if ($this->debug) {
                        echo $lineTMP[1] . "\n";
                        $lineTMP[1] = $lineTMP[2];
                    }
                    break;
                }
                case 2: {
                    $i = count($this->_inConditional);
                    //return of an if/else statement
                    $this->insertCode($functionName, $lineTMP[1], $i, $line);
                    array_pop($this->_inConditional);//remove the last one
                    break;
                }
                case 3: {//TODO: else
                    /*//set to the correct function name

                        if (count($this->_inConditional) > 0) {//we are in a conditional
                            $this->_functionName=end($this->_inConditional)['name'];
                        } else {
                            $this->_functionName = $_functionName;
                        }*/
                    if ($this->debug) {
                        $lineTMP[1] = 'else';
                    }
                    break;
                }
                case 4: {//multi-line response
                    foreach ($lineTMP as $index => $subLine) {
                        $i = count($this->_inConditional);
                        if (is_string($subLine)) {
                            if ($index === 1) {
                                $this->insertCode($functionName, $subLine, $i, $line);
                            } else {
                                $this->insertCode($functionName, $subLine, $i);
                            }
                            if ($this->debug) {
                                echo $subLine . "\n";
                            }
                        }
                    }
                    break;
                }
                case -1: {
                    $this->error('compile error (-1)');
                    break;
                }
                default: {
                    $this->error('unknown error (' . $lineTMP[0] . ')');
                    break;
                }
            }
            if ($this->debug) {
                echo $lineTMP[1] . "\n";
            }

            //check if a conditional just closed
            if ($this->_conditionalJustClosed !== false) {
                if (count($this->_inConditional) > 0) {
                    //we are in a conditional
                    $conName = end($this->_inConditional)['name'];
                    $i = count($this->_functionsCompiled[$conName]->returns);
                    $this->_functionsCompiled[$conName]->returns[] = [];
                    $this->_functionsCompiled[$conName]->returns[$i]['name']
                        = $this->_conditionalJustClosed['name'];
                    $this->_functionsCompiled[$conName]->returns[$i]['_line']
                        = $this->_lineNumber[$conName];
                    $this->_functionName = $conName;
                } else {
                    $i = count(
                        $this->_functionsCompiled[$functionName]->returns
                    );
                    $this->_functionsCompiled[$functionName]->returns[]
                        = [];
                    $this->_functionsCompiled[$functionName]->returns[$i]['name']
                        = $this->_conditionalJustClosed['name'];
                    $this->_functionsCompiled[$functionName]->returns[$i]['_line']
                        = $this->_lineNumber[$functionName];
                    $this->_functionName = $functionName;
                }

                //reset just closed
                $this->_conditionalJustClosed = false;
            }
        }
    }

    /**Inserts a _line of code
     *
     * @param string $functionName Where to insert
     * @param string $toInsert The _line to insert
     * @param int    $startLevel How many if/else levels up/down to insert
     * @param string $comment
     */
    private function insertCode($functionName, $toInsert, $startLevel, $comment = '')
    {
        $comment = trim($comment);
        if (!empty($comment) && $this->insertComments) {
            $toInsert .= ';' . $comment;
        }
        $i = $startLevel - 1;
        while ($i > -1 && $this->_inConditional[$i]['type'] === 'else') {
            $i--;
        }
        if ($i == -1) {
            $this->_functionsCompiled[$functionName]->code[] = $toInsert;
            $this->_lineNumber[$functionName]++;
        } else {
            $this->_functionsCompiled[$this->_inConditional[$i]['name']]->code[]
                = $toInsert;
            $this->_lineNumber[$this->_inConditional[$i]['name']]++;
        }
    }

    /** Create the actual compiled code
     *
     * @param $codeOutside array Code not in a function
     *
     * @return string The created code.
     */
    private function makeCode($codeOutside)
    {
        //set the line number in case we get an error
        $this->_functionName = 'COMPILER: making code';
        $this->_lineNumber['COMPILER: making code'] = 0;

        $result = [];

        //insert the _data
        $result[] = "@DATA";
        $result = array_merge($result, $this->_data);
        $result[] = '';

        $result[] = "@CODE";
        $result[] = '';
        foreach ($codeOutside as $returnCodeLine) {
            $result[] = $returnCodeLine;
        }
        $result[] = "begin:\tBRA main";
        $result[] = '';

        require_once 'defaultFunctions.php';

        foreach ($this->_useFunction as $name => $used) {
            if ($used) {
                $result = array_merge($result, $this->_defaultFunctions[$name]);
            }
        }

        //move the functions that where asked to do so
        foreach ($this->_moveFunction as $moveFunction)
        {
            $functionToMove=[];
            if (isset($this->_functionsCompiled[$moveFunction['name']]) && !empty($this->_functionsCompiled[$moveFunction['name']])) {
                $functionToMove[$moveFunction['name']]=$this->_functionsCompiled[$moveFunction['name']];
                unset($this->_functionsCompiled[$moveFunction['name']]);
            }
            else
            {
                //function not found!
                $this->error('Trying to move uncompiled function: '.$moveFunction['name']);
            }
            $before=array_slice($this->_functionsCompiled,0,$moveFunction['pos']);
            $after=array_slice($this->_functionsCompiled,$moveFunction['pos'],count($this->_functionsCompiled)-$moveFunction['pos']);
            $this->_functionsCompiled=array_merge($before,$functionToMove,$after);
        }

        //okay we have the outside code now, lets do the _functions
        $longestFunctionLength = 16;//beatify needs this.
        foreach ($this->_functionsCompiled as $funcName => $function) {
            $resultFunc = $this->makeFunc($funcName, $function);
            if (strlen($funcName) > $longestFunctionLength) {
                $longestFunctionLength = strlen($funcName);
            }

            if ($function->isConditional === true) {
                array_unshift($resultFunc, ';' . $function->statement);
                //$resultFunc[]=';}';
            }

            $resultFunc[] = "";//white line for readability
            $result = array_merge($result, $resultFunc);
        }
        $result[] = '@END';

        //we now have all code. Lets try and optimize.
        $result = $this->optimize($result);

        //we now have all code. Lets try and beatify it a bit.
        $result = $this->beautify($result, $longestFunctionLength);

        return implode("\n", $result);
    }

    /**Creates a function from semi-compiled code.
     *
     * @param string $funcName Name of the function
     * @param object $function Code of the function
     *
     * @return array compiled code
     */
    private function makeFunc($funcName, $function)
    {
        $result = [];
        foreach ($function->code as $i => $line) {
            if ($i == 0) {
                //first

                $result[] = $funcName . ": \t\t" . $line;
            } else {
                $result[] = "\t\t\t" . $line;
            }
        }
        //go back to main
        if (isset($this->_functions[$funcName]) and ($this->_functions[$funcName]->isTimer === true)) {
            $result[] = "\t\t\t" . 'RTE';
        } else {
            $result[] = "\t\t\t" . 'BRA main';
        }
        foreach ($function->returns as $return) {
            $result[$return['_line']]
                = $return['name'] . ':' . $result[$return['_line']];
        }
        return $result;
    }

    /** Optimizes the assembly code
     *
     * @param $result array The assembly code to optimize, split per rule
     *
     * @return string The optimized code.
     */
    public function optimize($result)
    {
        $branchPrev = false;
        $return = [];
        foreach ($result as $line) {
            //check if this is only an BRA
            if (preg_match("/^BRA.*/", trim($line))) {
                if ($branchPrev !== true) {
                    $return[] = $line;
                    $branchPrev = true;
                }
            } else {
                $branchPrev = false;
                $return[] = $line;
            }
        }

        return $return;
    }

    /** Does some simple optimizations
     *
     * @param $result                array The assembly code to beautify, split per rule
     * @param $longestFunctionLength int   The length of the name of the longest function
     *
     * @return string The beautified code.
     */
    public function beautify($result, $longestFunctionLength)
    {
        $codeStarted = false;
        $returnTmp = [];
        $lineLength = $longestFunctionLength + 4;
        $longestLineLength = 0;
        foreach ($result as $line) {
            if ($codeStarted === false) {
                $line = str_replace('  ', ' ', trim($line)); //replace multiple spaces and trim the line.
                if ($line == '@CODE') {
                    $codeStarted = true;
                }
            } else {
                $line = str_replace('  ', ' ', trim($line)); //replace multiple spaces and trim the line.
                if (!preg_match('/^.*:/', $line)) {
                    //insert spaces at the start if this is not the start of a instruction sequence
                    $line = str_repeat(" ", $lineLength) . $line;
                } else {
                    //insert spaces in between.
                    preg_match("/^(.*:)(\\s*)(.*)$/", $line, $matches);
                    $spaces = str_repeat(" ", $lineLength - strlen($matches[1]));
                    $line = $matches[1] . $spaces . $matches[3];
                }
            }
            $lineNoComment = $line;
            if (preg_match("/(.*);/", $line, $matches))//check for comments
            {
                $lineNoComment = $matches[1];
            }

            if (strlen($lineNoComment) > $longestLineLength) {
                $longestLineLength = strlen($lineNoComment);
            }

            if ($this->insertComments!==true) {
                $line = $lineNoComment;
            }
            $returnTmp[] = $line;
        }

        //no comments, so lets not beatify them
        if (!$this->insertComments) {
            return $returnTmp;
        }

        //beatify comments
        $return = [];
        $lineLength = $longestLineLength + 4;
        foreach ($returnTmp as $line) {
            //make comments nicer
            if (substr(trim($line), 0, 1) !== ';')//if the line is not a comment
            {
                if (preg_match("/(.*)(;.*)/", $line, $matches))//check for comments
                {
                    $spaces = str_repeat(" ", $lineLength - strlen($matches[1]));
                    $line = $matches[1] . $spaces . $matches[2];
                }
            } else {
                $spaces = str_repeat(" ", $lineLength);
                $line = $spaces . trim($line);
            }
            $return[] = $line;
        }
        return $return;
    }
}
