<?php

namespace App\Controllers;

use App\Models\BadgeModel;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;



/**
 * @OA\Tag(
 *   name="Badge",
 *     description="chat Api List [offer and chat api]"
 * )
 */
class Badge extends BaseController
{

    public function __construct()
    {
    }

    /**
     *  @OA\Post(
     *      tags={"Badge"},
     *      path="/badge/green_info",
     *      summary="redis save chat log",
     *      security={{"bearerAuth": {}}},
     *      @OA\Response(
     *          response=200,
     *          description=""
     *      ),
     *      @OA\Response(
     *          response="default",
     *          description="an ""unexpected"" error"
     *      ),          
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/x-www-form-urlencoded",
     *              @OA\Schema(     
     *                  @OA\Property(
     *                      description="hash_id",
     *                      property="hash_id",
     *                      type="string",
     *                  ),     
     *                  type="object",
     *              )
     *          )
     *      )
     *  )
     */
    public function green_info()
    {
        $input = $this->getRequestInputPostType($this->request); //check post
        $BadgeModel = new BadgeModel();
        $result = $BadgeModel->green_info($input['hash_id'] ?? "");

        if ($result['code'] != 200) return $this->getResponse($result, 200);

        $review = $BadgeModel->review_count($input['hash_id'] ?? "");

        $good_choice = [];
        $bad_choice = [];
        // $good_choice[0] = 0;
        // $good_choice[1] = 0;
        // $good_choice[2] = 0;
        // $good_choice[3] = 0;
        // $good_choice[4] = 0;

        // $bad_choice[0] = 0;
        // $bad_choice[1] = 0;
        foreach ($review['data'] as $key => $val) {
            if (!is_null($val['good_choice'])) {
                foreach (explode(',', $val['good_choice']) as $v) {
                    if (strlen($v) > 0) {
                        if (empty($good_choice[$v])) {
                            $good_choice[$v] = 1;
                        } else {
                            $good_choice[$v] = $good_choice[$v] + 1;
                        }
                    }
                }
            }

            if (!is_null($val['bad_choice'])) {
                foreach (explode(',', $val['bad_choice']) as $v) {
                    if (strlen($v) > 0) {
                        if (empty($bad_choice[$v])) {
                            $bad_choice[$v] = 1;
                        } else {
                            $bad_choice[$v] = $bad_choice[$v] + 1;
                        }
                    }
                }
            }
        }

        //print_r($good_choice);
        // sort($good_choice);
        // sort($bad_choice);
        ksort($good_choice);
        ksort($bad_choice);

        // if( count($good_choice) == 0 )  $good_choice = json_encode($good_choice, JSON_FORCE_OBJECT);
        // if( count($bad_choice) == 0 )  $bad_choice = json_encode($bad_choice, JSON_FORCE_OBJECT);
        // $result['data']['good_choice'] =  json_encode($good_choice, JSON_FORCE_OBJECT);
        // $result['data']['bad_choice'] =  json_encode($bad_choice, JSON_FORCE_OBJECT);

        $good_data = [];
        $i = 0;
        foreach ($good_choice as $key => $val) {
            $good_data[$i] = ['id' => $key, 'count' => $val];
            $i++;
        }

        $bad_data = [];
        $i = 0;
        foreach ($bad_choice as $key => $val) {
            $bad_data[$i] = ['id' => $key, 'count' => $val];
            $i++;
        }

        $result['data']['good_choice'] = $good_data;
        $result['data']['bad_choice'] =  $bad_data;
        $this->getResponseSend($result, 200);
    }

    /**
     *  @OA\Post(
     *      tags={"Badge"},
     *      path="/badge/green_info_content_list",
     *      summary="green_info_content_list",
     *      security={{"bearerAuth": {}}},
     *      @OA\Response(
     *          response=200,
     *          description=""
     *      ),
     *      @OA\Response(
     *          response="default",
     *          description="an ""unexpected"" error"
     *      ),          
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/x-www-form-urlencoded",
     *              @OA\Schema(     
     *                  @OA\Property(
     *                      description="hash_id",
     *                      property="hash_id",
     *                      type="string",
     *                  ),     
     *                  type="object",
     *              )
     *          )
     *      )
     *  )
     */
    public function green_info_content_list()
    {
        $input = $this->getRequestInputPostType($this->request); //check post
        $BadgeModel = new BadgeModel();

        $result = $BadgeModel->green_info_content_list($input);

        if ($result['code'] != 200) return $this->getResponse($result, 200);

        $this->getResponseSend($result, 200);
    }



    // public function get_question()
    // {
    //     $data = [];
    //     $data['good_recevied'][0] = ["id"=>1, "text"=>"Nice And Very Communicative", "type"=>"public"];
    //     $data['good_recevied'][1] = ["id"=>2, "text"=>"Right On Time", "type"=>"public"];
    //     $data['good_recevied'][2] = ["id"=>3, "text"=>"Fast Response", "type"=>"public"];
    //     $data['good_recevied'][3] = ["id"=>4, "text"=>"well mannered", "type"=>"public"];
    //     $data['good_recevied'][4] = ["id"=>5, "text"=>"Selling Good Products", "type"=>"dealer_only"];
    //     $data['bad_recevied'][0] = ["id"=>1, "text"=>"Unfriendly", "type"=>"public"];
    //     $data['bad_recevied'][1] = ["id"=>2, "text"=>"Keep on asking for a lower price with out intension of buying", "type"=>"onner_only"];
    //     echo json_encode($data);
    // }
}
