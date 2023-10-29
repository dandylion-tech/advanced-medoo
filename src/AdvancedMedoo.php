<?php
    namespace Dandylion;
    use Dandylion\Medoo;
    class AdvancedMedoo extends Medoo{
        private function convertColumns($columns){
            $column_data = array();
            if(is_array($columns)){
                $select = array();
                $uid_list = array();
                foreach($columns as $key=>$column){
                    $column_info = $this->convertColumns($column);
                    if(is_string($key)){
                        $select[$key] = $column_info["select"];
                    } else {
                        if(is_array($column_info["select"])){
                            $select = array_merge($select,$column_info["select"]);
                        } else {
                            $select[] = $column_info["select"];
                        }
                    }
                    if(isset($column_info["uid"])){
                        $uid_list[] = $column_info["uid"];
                    } elseif(isset($column_info["uid_list"])){
                        $uid_list = array_merge($uid_list,$column_info["uid_list"]);
                    }
                }
                return array("uid_list"=>$uid_list,"select"=>$select);
            } elseif(is_string($columns)){
                preg_match("/([^\. ]+)(?:\.([^\. ]+))?(?:\s+\(([^\)]+)\))?/",$columns,$matches);
                if(!isset($matches[2])||$matches[2]!="*")return array("select"=>$columns,"uid_list"=>array());
                $column_list = array_column($this->query("DESCRIBE <".$matches[1].">")->fetchAll(\PDO::FETCH_ASSOC),"Field");
                $uid = implode("",array_map(function($item){
                    return chr(random_int(0,1)?random_int(65,90):random_int(97,122));
                },array_fill(0,8,0)));
                foreach($column_list as $column){
                    $column_data[] = $matches[1].".".$column." (".$uid."_".($matches[3]??"").$column.")";
                }
                return array("uid"=>$uid,"select"=>$column_data);
            } else {
                return array("select"=>$columns);
            }
        }
        private function removeUIDs($data,$uid_list){
            $return_data = array();
            foreach($data as $column=>$value){
                if(is_array($value)){
                    $return_data[$column] = $this->removeUIDs($value,$uid_list);
                } else {
                    $found = false;
                    foreach($uid_list as $uid){
                        if(strpos($column,$uid) === 0){
                            $column = str_replace($uid."_","",$column);
                            $return_data[$column] = $value;
                            $found=true;
                            break;
                        }
                    }
                    if(!$found)$return_data[$column] = $value;
                }
            }
            return $return_data;
        }
        protected function isJoin($join):bool{
            if(!is_array($join))return false;
            $keys = array_keys($join);
            return isset($keys[0])&&is_string($keys[0])&&strpos($keys[0], '[') === 0;
        }
        public function get(string $table, $join = null, $columns = null, $where = null){
            $is_join = $this->isJoin($join);
            $column_data = $is_join?$columns:$join;
            $column_data = $this->convertColumns($column_data);
            if(isset($column_data["uid"])){
                $uid_list = array($column_data["uid"]);
            } else {
                $uid_list = $column_data["uid_list"];
            }
            $select = $column_data["select"];
            if($is_join){
                $columns = $select;
            } else {
                $join = $select;
            }
            $results = parent::get(
                $table,
                $join,
                $columns,
                $where
            );
            if(is_array($results)){
                return $this->removeUIDs($results,$uid_list);
            } else {
                return $results;
            }
        }
        public function select(string $table, $join, $columns = null, $where = null): ?array{
    
    
            $is_join = $this->isJoin($join);
            $column_data = $is_join?$columns:$join;
            $column_data = $this->convertColumns($column_data);
    
    
            if(isset($column_data["uid"])){
                $uid_list = array($column_data["uid"]);
            } else {
                $uid_list = $column_data["uid_list"];
            }
            $select = $column_data["select"];
    

            $select = $this->similarity($select);
    
            if($is_join){
                $columns = $select;
            } else {
                $join = $select;
            }
    
    
            $results = parent::select(
                $table,
                $join,
                $columns,
                $where
            );
           
            if(is_array($results)){
                return $this->removeUIDs($results,$uid_list);
            } else {
                return $results;
            }
        }
        public function patch(string $table, $data, $unique_column_list){
            if(is_string($unique_column_list))$unique_column_list = [$unique_column_list];
            if(!array_is_list($data))$data = [$data];
            $where = [];
            foreach($data as $index=>$row){
                $and = [];
                foreach($unique_column_list as $column){
                    $and[$column] = $row[$column];
                }
                $where["AND #".$index] = $and;
            }
            $where = ["OR"=>$where];
            $current_items = $this->select($table,array_merge(array_keys(reset($data)),array("unique"=>$unique_column_list)),$where);
            $update_list = [];
            $insert_list = [];
            $unique_current_items = array_column($current_items,"unique");
            array_walk($current_items,function(&$item){
                unset($item["unique"]);
            });
            foreach($data as $row){
                $unique_row = array_combine($unique_column_list,array_map(function($column) use($row){
                    return $row[$column];
                },$unique_column_list));
                if(array_search($unique_row,$unique_current_items) !== false){
                    if(array_search($row,$current_items) === false){
                        $update_list[] = $row;
                        $this->update($table,$row,$unique_row);
                    }
                } else {
                    $insert_list[] = $row;
                }
            }
            if(count($insert_list))$this->insert($table,$insert_list);
        }
        public function delete(string $table,$where): ?\PDOStatement{
            return parent::delete($table,$where);
        }
        public function sync(string $table, $data, $unique_column_list){
            if(is_string($unique_column_list))$unique_column_list = [$unique_column_list];
            if(!array_is_list($data))$data = [$data];
            $where = [];
            foreach($data as $index=>$row){
                $and = [];
                foreach($unique_column_list as $column){
                    $and[$column."[!]"] = $row[$column];
                }
                $where["OR #".$index] = $and;
            }
            $where = ["AND"=>$where];
            parent::delete($table,$where);
            $this->patch($table,$data,$unique_column_list);
        }
        protected function similarity($select){
            global $database;
            foreach($select as $key=>$value){
                if(preg_match("/^SIMILAR\((.+)\)$/",$key,$matches)){
                    if(!is_array($value)||!array_is_list($value)){
                        $value = array($value);
                    }
                    foreach($value as $column){
                        preg_match("/^([^\)]+)(?: +\(([^\)]+)\))?$/",$column,$column_names);
                        $select[$column_names[2]] = $database::raw("SIMILAR(:search,<".$column_names[1].">)",[":search"=>$matches[1]]);
                    }
                    unset($select[$key]);
                } elseif(is_array($value)){
                    $select[$key] = $this->similarity($value);
                }
            }
            return $select;
        }
        public function replace(string $table, array $columns, $where = null): ?\PDOStatement{
            $return = parent::replace($table,$columns,$where);
            return $return;
        }
        public function insert(string $table, array $values, string $primaryKey = null): ?\PDOStatement{
            $inserted_data = parent::insert($table,$values,$primaryKey);
            return $inserted_data;
        }
    }
?>
