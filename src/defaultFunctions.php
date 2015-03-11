<?php
$this->_defaultFunctions['display'] = "
;display
_Hex7Seg:     BRS  _Hex7Seg_bgn  ;  push address(tbl) onto stack and proceed at bgn
_Hex7Seg_tbl: CONS  %01111110    ;  7-segment pattern for '0'
              CONS  %00110000    ;  7-segment pattern for '1'
              CONS  %01101101    ;  7-segment pattern for '2'
              CONS  %01111001    ;  7-segment pattern for '3'
              CONS  %00110011    ;  7-segment pattern for '4'
              CONS  %01011011    ;  7-segment pattern for '5'
              CONS  %01011111    ;  7-segment pattern for '6'
              CONS  %01110000    ;  7-segment pattern for '7'
              CONS  %01111111    ;  7-segment pattern for '8'
              CONS  %01111011    ;  7-segment pattern for '9'
              CONS  %01110111    ;  7-segment pattern for 'A'
              CONS  %00011111    ;  7-segment pattern for 'b'
              CONS  %01001110    ;  7-segment pattern for 'C'
              CONS  %00111101    ;  7-segment pattern for 'd'
              CONS  %01001111    ;  7-segment pattern for 'E'
              CONS  %01000111    ;  7-segment pattern for 'F'
_Hex7Seg_bgn:   AND  R5  %01111   ;  R0 := R0 MOD 16 , just to be safe...
              LOAD  R4  [SP++]   ;  R4 := address(tbl) (retrieve from stack)
              LOAD  R4  [R4+R5]  ;  R4 := tbl[R0]
              LOAD  R5  ".IOAREA."
              STOR  R4  [R5+".DSPSEG."] ; and place this in the Display Element
               RTS";
$this->_defaultFunctions['display'] = explode("\n", $this->_defaultFunctions['display']);

$this->_defaultFunctions['sleep'] = "
;sleep
_timer: MULS  R5  10
        LOAD  R4  R5
        LOAD  R5  ".IOAREA."
        LOAD  R5  [R5+".TIMER."]
        SUB   R5  R4
        LOAD  R4  ".IOAREA."
_wait:  CMP   R5  [R4+".TIMER."]       ;  Compare the timer to 0
        BMI   _wait
        RTS";
$this->_defaultFunctions['sleep'] = explode("\n", $this->_defaultFunctions['sleep']);

$this->_defaultFunctions['pow'] = "
;pow
_pow:   	CMP R4 0
            BEQ _pow1
            CMP R4 1
            BEQ _powR
            PUSH R3
            PUSH R4
            SUB R4 1
			LOAD R3 R5
_powLoop:	MULS R5 R3
		 	SUB R4 1
			CMP R4 0
			BEQ _powReturn
			BRA _powLoop
_powReturn: PULL R4
            PULL R3
			RTS
_pow1:      LOAD R5 1
            RTS
_powR:      RTS";
$this->_defaultFunctions['pow'] = explode("\n", $this->_defaultFunctions['pow']);

$this->_defaultFunctions['pressed'] = "
;pressed
_pressed: 	PUSH R4
            LOAD R4 R3
            LOAD R5 2
            BRS _pow
            LOAD R3 R5
            LOAD R5 ".IOAREA."
            LOAD R4 [R5+".INPUT."]
            DIV R4 R3
            MOD R4 2
            LOAD R5 R4
            PULL R4
            RTS";
$this->_defaultFunctions['pressed'] = explode("\n", $this->_defaultFunctions['pressed']);
