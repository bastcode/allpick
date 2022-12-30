<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;
use Elastic\Elasticsearch\ClientBuilder;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;


class EsModel extends Model
{
    protected $table = 'es not table';

    public function client()
    {
        return ClientBuilder::create()
            ->setHosts(['es-container:9200'])
            ->build();
    }


    /**
     * 자동완성형 검색엔진
     */
    public function search_auto_complete(array $keywords)
    {
        $client = $this->client();
        //print_r($keyword); exit;
        //$keyword = ['iphone','max'];
        //print_r($keyword);

        $keyword = [];
        foreach($keywords as $key =>$val) {
            $keyword[$key] = strtolower($val);
        }

        $params = [
            'index' => 'product',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'terms' => ['status.keyword' => [1, 2, 3]],
                        ],
                        'filter' => [
                            'terms' => ['product_name' => $keyword],
                        ],
                        'should' => [
                            'terms' => ['product_name' => $keyword],                            
                        ],
                        "minimum_should_match" => 1
                    ]
                ]
            ]
        ];

        $results = $client->search($params);
        $data = [];
        
        //print_r($results['hits']['hits']);
        if ($results['hits']['hits']) {
            if( !is_array($results['hits']['hits'])) {
                echo 'not arr'; exit;
                $results['hits']['hits'] = json_decode($results['hits']['hits'], true);
            }
            foreach ($results['hits']['hits'] as $key => $val) {
                //$data[$key] = $val['_source'];
                //unset($data[$key]['mem_idx']); //user idx remove
                $data[$key]['product_id'] = $val['_source']['product_id'];
                $data[$key]['product_name'] = $val['_source']['product_name'];
                $i = 0;
                foreach ($data as $k => $v) {
                    if ($val['_source']['product_id'] == $v['product_id']) {
                        $i++;
                    }
                    if ($i > 1)  unset($data[$k]); //over rows remove
                }
            }
        } else {
            $data = [];
        }

        return $data;
    }

    /**
     * 상품 검색엔진에 등록
     */
    public function put_product(array $data)
    {
        $client = $this->client();
        $params = [
            'index' => 'product',
            'body'  => [
                'product_id' => $data['product_id'],
                'mem_idx' => $data['mem_idx'],
                'product_name' => $data['product_name'],
                'product_name_match' => $data['product_name'],
                'price' => $data['price'],
                'content' => $data['content'],
                'status' => $data['status'],
                'product_latitude' => $data['product_latitude'],
                'product_longitude' => $data['product_longitude'],
                'updated_at' => $data['updated_at']
            ]
        ];

        try {
            $response = $client->index($params);
        } catch (ClientResponseException $e) {
            // manage the 4xx error
        } catch (ServerResponseException $e) {
            // manage the 5xx error
        } catch (\Exception $e) {
            // eg. network error like NoNodeAvailableException
        }
    }

    /**
     * 검색엔진 상품 상태값 업데이트
     */
    public function update_product_status($data, $status)
    {
        $client = $this->client();
        $params = [
            'index' => 'product',
            'body'  => [
                'query' => [
                    'match' => [
                        'product_id' => $data['product_id']
                    ]
                ]
            ]
        ];

        $results = $client->search($params);
        
        try {
            if ($results['hits']['hits']) {

                $params = [
                    'index' => 'product',
                    'id'    => $results['_id'],
                    'body'  => [
                        'doc' => [
                            'status' => $status
                        ]
                    ]
                ];
                $results = $client->update($params);
            }
        } catch (ClientResponseException $e) {
            // manage the 4xx error
            //echo 'manage';
        } catch (ServerResponseException $e) {
            // manage the 5xx error
            //echo '5xx';
        } catch (\Exception $e) {
            // eg. network error like NoNodeAvailableException
            //echo $e;
        }
    }

    public function delete_forced_product($product_id)
    {
        $client = $this->client();
        $params = [
            'index' => 'product',
            'body'  => [
                'query' => [
                    'match' => [
                        'product_id' => $product_id
                    ]
                ]
            ]
        ];

        $results = $client->search($params);

        try {
            if ($results['hits']['hits']) {
                $results = $client->delete($results['_id']);
            }
        } catch (ClientResponseException $e) {
            // manage the 4xx error
        } catch (ServerResponseException $e) {
            // manage the 5xx error
        } catch (\Exception $e) {
            // eg. network error like NoNodeAvailableException
        }
    }


    public function get_anal($keyword)
    {
        $client = $this->client();
        $params = [
            'index' => 'product',
            'pretty'=>true,
            'body'  => [
                'tokenizer' => 'standard',
                'filter' => ['lowercase', 'stop', 'kstem'],
                "text" => $keyword
            ]
        ];

        

        $results = $client->indices()->analyze($params);
        $data = json_decode($results, true);
        $where_in = [];
        $alram_list = [];

        if($data['tokens'] ){
            foreach($data['tokens'] as $key => $val){
                array_push($where_in, $val['token']);
            }            
        }


        if( count($where_in) > 0  ) {
            $db = db_connect();
            $builder = $db->table('member_keywords')->select('DISTINCT member_id', false)->whereIn('keyword', $where_in);
            $alram_list = $builder->get()->getResultArray();
        }
        return $alram_list;

    }

    public function get_product_keyword_push_token($keyword)
    {
        $client = $this->client();
        $params = [
            'index' => 'product',
            'pretty'=>true,
            'body'  => [
                'tokenizer' => 'standard',
                'filter' => ['lowercase', 'stop', 'kstem'],
                "text" => $keyword
            ]
        ];

        // log_message('notice', 'get_product_keyword_push_token ' . $keyword);
        

        $results = $client->indices()->analyze($params);
        $data = json_decode($results, true);
        $where_in = [];
        $alram_list = [];

        //토큰라이저 토큰만 가져오기 > 토큰이 상품명
        if($data['tokens'] ){
            foreach($data['tokens'] as $key => $val){
                array_push($where_in, $val['token']);
            }
        }

        //log_message('info', 'tokens ' . json_encode($where_in));

        //토큰으로 잘린 상품명으로 해당 상품 검색
        $params = [
            'index' => 'product',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'terms' => ['status.keyword' => [1, 2, 3]],   //미 오픈은 제외
                        ],
                        'filter' => [
                            'terms' => ['product_name' => $where_in],
                        ],
                        'should' => [
                            'terms' => ['product_name' => $where_in],
                        ],
                        "minimum_should_match" => 1
                    ]
                ]
            ]
        ];

        $results = $client->search($params);

        log_message('info', 'elastic_data == ' . json_encode($results['hits']['hits']));

        $data_product = [];
        if ($results['hits']['hits']) {            
            foreach ($results['hits']['hits'] as $key => $val) {
                $data_product[$key] = $val['_source'];
                unset($data_product[$key]['mem_idx']); //user idx remove
                $i = 0;
                foreach ($data_product as $k => $v) {
                    if ($val['_source']['product_id'] == $v['product_id']) {
                        $i++;
                    }
                    if ($i > 1)  unset($data_product[$k]); //over rows remove
                }
            }
        }

        if( count($where_in) > 0  ) {
            $db = db_connect();
            $builder = $db->table('member_keywords mk')
            ->select('DISTINCT mpt.device_id, mpt.type, mpt.`member_id`', false)
            ->join('member_push_toekn mpt', ' mk.`member_id` = mpt.`member_id` ','inner')
            ->whereIn('mk.keyword', $where_in);
            $alram_list = $builder->get()->getResultArray();
            // log_message('info', 'get_product_keyword_push_token ' . $db->lastQuery);

            //
            $whereInMember = [];
            foreach($alram_list as $k=>$v) {
                array_push($whereInMember, $v['member_id']);
            }
            
            $alram_config_list = [];
            if( count($whereInMember) > 0) {
                //키워드 매칭 회원이 1개 이상이여야함
                $db = db_connect();
                $builder = $db->table('member_alarm')->whereIn('member_id',$whereInMember);
                $alram_config_list = $builder->get()->getResultArray();
            }
            foreach($alram_list as $key=>$val) {
                foreach($alram_config_list as $k=>$v) {
                    if($v['member_id'] == $val['member_id'] && $v['keyword'] == 0) {
                        //회원 키워드 알림이 0 꺼짐인경우 제외                        
                        unset($alram_list[$key]);
                    }
                }                
            }

            //키워드 알림 추가            
            // log_message('notice', 'sort product list === ' . json_encode($data_product));
            log_message('notice', 'add keyword alram member list === ' . json_encode($alram_list));
            $builder = $db->table('member_alerts_keywords');
            foreach($data_product as $k => $v) {
                foreach($alram_list as $key => $val) {
                    $builder->insert([
                        'member_id'=>$val['member_id'],
                        'product_id'=>$v['product_id'],
                        'message'=>$v['product_name'],
                        'keyword'=>$keyword
                    ]);
                }
            }
        }
        
        return $alram_list;

    }
}
