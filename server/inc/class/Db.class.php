<?php

    class Db extends PDO {
        private $smt;

        public function __construct($options, $attributes) {
            parent::__construct(SERVER, USER, PW, $options);
            foreach ($attributes as $key => $value) {
                $this -> setAttribute($value[0], $value[1]);
            }
        }

        public function selectOne($query) {
            //$stmt   = $this -> $query($query);
            $this  -> $stmt   = $this -> $query($query);
            $result = $stmt -> fetch(FETCH);
            return $result;
        }

        public function selectMultiple($query) {
            $this  -> $smt   = $this -> $query($query);
            $result = $this -> $smt  -> fetchALL(FETCH);
            return $result;
        }

        public function change($query){
            return $this -> exec($query);
        }

        public function insert($query, $settings){
            $stmt   = $this -> prepare($query);
            return $stmt -> execute($settings);
        }

        public function preparedStatement($query, $params){
            $stmt   = $this -> prepare($query);
            for ($i = 0; $i <= count($params); $i++) {
                $stmt -> execute($params[$i]);
            }
            //return $stmt;
        }
        
        public function run($sql, $args = NULL){
            if (!$args){
                return $this->query($sql);
            }
            $stmt = $this->prepare($sql);
            $stmt->execute($args);
            return $stmt;
        }

    }
?>