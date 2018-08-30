<?php

if(!class_exists('WPLMS_Customizer_Plugin_Class'))
{   
    class WPLMS_Customizer_Plugin_Class  // We'll use this just to avoid function name conflicts 
    {
            
        public function __construct(){   
            
        } // END public function __construct
        public function activate(){
        	// ADD Custom Code which you want to run when the plugin is activated
        }
        public function deactivate(){
        	// ADD Custom Code which you want to run when the plugin is de-activated	
        }
        
        // ADD custom Code in clas
        
    } // END class WPLMS_Customizer_Class
} // END if(!class_exists('WPLMS_Customizer_Class'))

?>