<?php
    namespace Dandylion;
    use Medoo\Medoo;
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
                        $select = array_merge($select,$column_info["select"]);
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
        public function select(string $table, $join, $columns = null, $where = null): ?array{
    
    
            $is_join = is_array($join);
            if(is_array($join)){
                $keys = array_keys($join);
                $is_join = isset($keys[0])&&is_string($keys[0])&&strpos($keys[0], '[') === 0;
            }
            $column_data = $is_join?$columns:$join;
            $column_data = $this->convertColumns($column_data);
    
    
            $uid_list = $column_data["uid_list"];
            $select = $column_data["select"];
    
    
            if($is_join){
                $columns = $select;
            } else {
                $join = $select;
            }
    
    
            $results = parent::select(
                $table,
                $join,
                $columns??null,
                $where??null
            );
           
            return $this->removeUIDs($results,$uid_list);
        }
    }
?>