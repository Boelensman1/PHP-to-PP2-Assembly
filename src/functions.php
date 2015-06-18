<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file contains the functions of the compiler.
 *
 * Include this in your code for autocompletion.
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
 * @copyright 2015 Wigger Boelens
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version   v0.2
 */

/**
 * Store a value in the ram.
 *
 * Example: storeRam($location,$value)
 *
 * @param mixed $location The location (a variable) to store the value in the ram
 * @param mixed $value    The value to store, needs to be a variable
 *
 * @return void
 */
function storeRam($location, $value)
{
}

/**
 * Get a value from the ram.
 *
 * Example: $value=getRam($location)
 *
 * @param mixed $location The location (a variable) where the value is stored
 *
 * @return mixed The value that is stored at the location
 */
function getRam($location)
{
}

/**
 * Display something on either the display or the leds
 *
 * Possible values for $onwhat:
 * leds: the leds at the top
 * leds2: the leds to the right
 * display: the display
 * Example:
 * display($value, 'display',000100)
 * This will display $value in the middle of the display
 *
 * @param mixed    $what     what to display, must be a variable
 * @param string   $onWhat   on what to display
 * @param string   $location Where to show the value when using the display,
 *                           defaults to the right position
 *
 * @return void
 */
function display($what, $onWhat, $location = '000001')
{
}

/**
 * Get the power of a number
 *
 * Example: $temp=pow(2,$power)
 * This will make $temp equal to 2^$power
 *
 * @param mixed $number the number to power
 * @param mixed $power  the power value
 *
 * @return int The result
 */
function pow($number,$power)
{
}

/**
 * Take the mod of a number
 *
 * Example: mod($variable,2)
 * This will return the mod 2 of $variable
 *
 * @param int      $what     modulo what
 * @param mixed $variable variable to modulo over
 *
 * @return void
 */
function mod($what, $variable)
{
}

/**
 * Get button or analog input
 *
 * When you just want hte input of 1 button, use getButtonPressed instead
 * Example: getInput($variable,'analog')
 * This will put the value of the analog into $variable
 *
 * @param mixed $writeTo Variable to write the input to
 * @param string $type Type of input, possible values are: buttons, analog
 *
 * @return void
 */
function getInput($writeTo, $type)
{
}

/**
 * Check if a button is pressed
 *
 * Puts the result into R5
 * Example: $pressed=getButtonPressed($location);
 *
 * @param mixed $button Which button to check (input a variable)
 *
 * @return int Whether or not the button is pressed.
 */
function getButtonPressed($button)
{
}

/**
 * Install the countdown
 *
 * Do not forget to add returnt at the end of the interrupt function
 * Example: installCountdown('timerInterrupt')
 * This will install the countdown.
 * In this example when the timer interrupt triggers,
 * the function timerInterrupt is ran.
 *
 * @param string $functionName The name of the function where the timer should go to
 *
 * @return void
 */
function installCountdown($functionName)
{
}

/**
 *Start the countdown.
 *
 * @return void
 */
function startCountdown()
{
}

/**
 *Push a variable to the stack
 *
 * @param string $variable the variable to push to the stack
 *
 * @return void
 */
function pushStack($variable)
{
}

/**
 *Pull a mixed from the stack
 *
 * @param string $variable the variable where the pulled variable is put into
 *
 * @return void
 */
function pullStack($variable)
{
}

/**
 * Set the timer interrupt to a value.
 *
 * It will first reset the timer to 0.
 * Example: setTimer(10)
 * This will interrupt the program after 10 timer ticks
 *
 * @param int $countdown how long the countdown should wait, in timer ticks
 *
 * @return void
 */
function setCountdown($countdown)
{
}


/**
 * Get data
 *
 * Use offset 0 when it is just a single value.
 * Example: $data=getData('data',1)
 * This will put the value of the data segment "data" at position 1, into $data.
 *
 * @param string $location The location where the variable is stored
 * @param int    $offset   The offset of the location
 *
 * @return mixed The value of the data segment
 */
function getData($location, $offset)
{
}

/**
 * Store data
 *
 * Use offset 0 when it is just a single value.
 * Example: storeData($data,'data',1)
 * This will put the value of $data into the data segment "data" at position 1
 *
 * @param mixed $variable The variable to store
 * @param string   $location The name of the location where the variable is stored
 * @param int      $offset   The offset of the location
 *
 * @return void
 */
function storeData($variable, $location, $offset)
{
}


/**
 * Pause the program
 *
 * Example:
 * sleep(10)
 * This will sleep for 10 clockticks
 *
 * @param int $howLong How long to sleep
 *
 * @return void
 */
function sleep($howLong)
{
}


/**
 * Init a variable that is used in that data segment
 *
 * Example:
 * initVar('outputs', 10);
 * This will init the data segement outputs and reserve 10 spots
 * If you just want to save a single variable, set $places to 1
 *
 * @param string $variable The name of the variable
 * @param int    $places How long the array is
 *
 * @return void
 */
function initVar($variable,$places)
{
}


/**
 * Branch to a function
 *
 * Example:
 * branch('test');
 * This will branch to the function test
 *
 * @param string $branchTO where to branch to
 *
 * @return void
 */
function branch($branchTO)
{
}

/**
 * Move a function in the assembly code
 *
 * Example:
 * moveFunction('function',3);
 * This will move 'function' to the 3rd position
 * Of course only in the assembly code.
 * It's the 3rd position after the standard functions.
 *
 * @param string $branchTO where to branch to
 *
 * @return void
 */
function moveFunction($branchTO)
{
}