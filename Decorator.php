<?php
    
    /**
    * Wraps in a decorator all the methods being called so that
    * the user rights can be checked seamlessly at every instance
    */
    class Decorator extends Permessi
    {
        /**
        * The class name being invoked
        * @var string
        */
        protected $classe;
        
        /**
        * The array of actions implemented by the class
        * @var array
        */
        protected $azioni;
        
        function __construct($classe) {
            $this->classe = $classe;
            $this->_db = Database::Load();
            $this->azioni = $this->classe->getAzioni();
            $this->controllaPresenza($this->azioni);
        }
    
        function __call($method_name, $args) {
            /**
            * Check if the method being is authorized according to the user's rights
            */
           if($this->controllaMetodo($method_name, $_SESSION["user"]["level"], $this->azioni)) {
               return call_user_func_array(array($this->classe, $method_name), $args);
           } else {
              /**
              * TODO
              * Implement a higher level management for unautorized actions
              */
              echo 'NO-AUTH';
           }
        }
    }
?>