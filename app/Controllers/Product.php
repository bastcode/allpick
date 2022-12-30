<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\EsModel;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;
use Aws\Resource\Aws;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\MultipartUploader;
use Aws\Exception\MultipartUploadException;
use Aws\Credentials\CredentialProvider;
use SebastianBergmann\CodeCoverage\BranchAndPathCoverageNotSupportedException;

/**
 * @OA\Tag(
 *     name="Product",
 *     description="product api list [상품 CURD 거래 등]"
 * )
 */
class Product extends BaseController
{
    /**
     *  @OA\Get(
     *      tags={"Product"},
     *      path="/product/product_list",
     *      security={{"bearerAuth": {}}},
     *      summary="product list  [메인 화면 상품리스트]",
     *      @OA\Response(
     *          response=200,
     *          description="product_list"
     *      ),     
     * 
     *  @OA\Parameter(
     *   parameter="top_input_search",
     *   name="top_input_search",
     *   @OA\Schema(
     *     type="string",
     *   ),
     *   in="query",
     *   required=false,
     *   description="상품명 키워드",
     *   ),
     *  @OA\Parameter(
     *   parameter="search_location",
     *   name="search_location",
     *   @OA\Schema(
     *     type="number",     
     *     default="250"
     *   ),
     *   in="query",
     *   required=false,
     *   description="검색 거리 제한 250M~10000M 빈 값이나 0 주면 제한없음 거리제한없으면 위 경도 반영안함 ",
     *   ),
     *  @OA\Parameter(
     *   parameter="page",
     *   name="page",
     *   @OA\Schema(
     *     type="number",
     *     default="1"
     *   ),
     *   in="query",
     *   required=false,
     *   description="페이지 넘버 [스크롤 next 일때 +1]",
     *   ),
     * 
     *  @OA\Parameter(
     *   parameter="latitude",
     *   name="latitude",
     *   @OA\Schema(
     *     type="number",
     *     default="1.292861"
     *   ),
     *   in="query",
     *   required=false,
     *   description="위도 [gps]"
     *   ),
     * 
     *  @OA\Parameter(
     *   parameter="longitude",
     *   name="longitude",
     *   @OA\Schema(
     *     type="number",
     *     default="103.852689"
     *   ),
     *   in="query",
     *   required=false,
     *   description="경도[gps]"
     *   ),
     * 
     *  @OA\Parameter(
     *   parameter="category",
     *   name="category",
     *   @OA\Schema(
     *     type="number",
     *     default="-1"
     *   ),
     *   in="query",
     *   required=false,
     *   description="카테고리코드 > -1 이면 카테고리 전체"
     *   ),
     *  @OA\Parameter(
     *   parameter="min_price",
     *   name="min_price",
     *   @OA\Schema(
     *     type="number",
     *     default="-1"
     *   ),
     *   in="query",
     *   required=false,
     *   description="상품 가격 최소값 검색 > -1 제한없음"
     *   ),
     *  @OA\Parameter(
     *   parameter="max_price",
     *   name="max_price",
     *   @OA\Schema(
     *     type="number",
     *     default="-1"
     *   ),
     *   in="query",
     *   required=false,
     *   description="상품 가격 최대값 검색 > -1 제한없음"
     *   ),
     *  @OA\Parameter(
     *   parameter="period",
     *   name="period",
     *   @OA\Schema(
     *     type="number",
     *     default="0"
     *   ),  
     *   in="query",
     *   required=false,
     *   description="상품 등록기간 검색 0 무제한  1 3 7 day 기준"
     *   ),
     *  @OA\Parameter(
     *   parameter="compleate",
     *   name="compleate",
     *   @OA\Schema(
     *     type="number",
     *     default="0"
     *   ),
     *  @OA\Parameter(
     *   parameter="free item",
     *   name="is_free",
     *   @OA\Schema(
     *     type="number",
     *     default="0"
     *   ),
     *   in="query",
     *   required=false,
     *   description="거래완료 필터 0 없음 1 필터링"
     *  ),  
     * )
     */
    public function product_list()
    {

        $input = $this->getRequestInputGetType($this->request);
        $top_input_search = $input["top_input_search"] ?? "";
        $search_location = $input["search_location"] ?? 0;
        $category = $input["category"] ?? -1;
        $min_price = $input["min_price"] ?? -1;
        $max_price = $input["max_price"] ?? -1;
        $latitude = $input["latitude"] ?? "";
        $longitude = $input["longitude"] ?? "";
        $period = $input["period"] ?? 0;
        $page = $input["page"] ?? 0;
        $compleate = $input["compleate"] ?? 0;
        $is_free = $input["is_free"] ?? null;

        $jwt_std = $this->init_jwt_info_pass();
        if ($jwt_std['code'] == 301) {
            //다시 체크해서 로그인 정보 가져옴
            $member_info = $this->init_jwt_info();
            $mem_id = $member_info['data']['member_id'];
        } else {
            $mem_id = 0; //없는 비로그인 맴버로 조회
        }

        $ProductModel = new ProductModel();
        $result = $ProductModel->product_list($top_input_search, $search_location, $page, $mem_id, $category, $min_price, $max_price, $latitude, $longitude, $period, $compleate, $is_free);

        if ($top_input_search) {
            $Redis = new Redis();
            $Redis->zadd($top_input_search);
        }

        $this->getResponseSend(["code" => 200, "message" => "product list now", "page" => $page, "data" => $result], 200);
    }

    /**
     *  @OA\Post(
     *      tags={"Product"},
     *      path="/product/popular_keywords",
     *      summary="popular_keywords 인기 검색어  keyword : score point 인기순 rank",     
     *      @OA\Response(
     *          response=200,
     *          description=" "
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
     *                  type="object",
     *              )
     *          )
     *      )
     *  )
     */
    public function popular_keywords()
    {
        $Redis = new Redis();
        $data = $Redis->zrevrange();
        $keyword_array = [];

        foreach ($data as $key => $val) {
            array_push($keyword_array, ["name" => $key, "count" => $val]);
        }
        $this->getResponseSend(["code" => 200, "message" => "popular keywords", "data" => $keyword_array], 200);
    }

    /**
     *  @OA\Get(
     *      tags={"Product"},
     *      path="/product/search_list",
     *      security={{"bearerAuth": {}}},
     *      summary="search product list  [검색 상품 리스트] - 비회원은 위 경도 검색안됌",
     *      @OA\Response(
     *          response=200,
     *          description="product_list"
     *      ),
     * 
     *  @OA\Parameter(
     *   parameter="top_input_search",
     *   name="top_input_search",
     *   @OA\Schema(
     *     type="string",
     *   ),
     *   in="query",
     *   required=false,
     *   description="상품명 키워드",
     *   ),
     *  @OA\Parameter(
     *   parameter="search_location",
     *   name="search_location",
     *   @OA\Schema(
     *     type="number",     
     *     default="250"
     *   ),
     *   in="query",
     *   required=false,
     *   description="검색 거리 제한 250M~10000M 빈 값이나 0 주면 제한없음 거리제한없으면 위 경도 반영안함 ",
     *   ),
     *  @OA\Parameter(
     *   parameter="page",
     *   name="page",
     *   @OA\Schema(
     *     type="number",
     *     default="1"
     *   ),
     *   in="query",
     *   required=false,
     *   description="페이지 넘버 [스크롤 next 일때 +1]",
     *   ),
     * 
     *  @OA\Parameter(
     *   parameter="latitude",
     *   name="latitude",
     *   @OA\Schema(
     *     type="number",
     *     default="1.292861"
     *   ),
     *   in="query",
     *   required=false,
     *   description="위도 [gps]"
     *   ),
     * 
     *  @OA\Parameter(
     *   parameter="longitude",
     *   name="longitude",
     *   @OA\Schema(
     *     type="number",
     *     default="103.852689"
     *   ),
     *   in="query",
     *   required=false,
     *   description="경도[gps]"
     *   ),
     * 
     *  @OA\Parameter(
     *   parameter="category",
     *   name="category",
     *   @OA\Schema(
     *     type="number",
     *     default="-1"
     *   ),
     *   in="query",
     *   required=false,
     *   description="카테고리코드 > -1 이면 카테고리 전체"
     *   ),
     *  @OA\Parameter(
     *   parameter="min_price",
     *   name="min_price",
     *   @OA\Schema(
     *     type="number",
     *     default="-1"
     *   ),
     *   in="query",
     *   required=false,
     *   description="상품 가격 최소값 검색 > -1 제한없음"
     *   ),
     *  @OA\Parameter(
     *   parameter="max_price",
     *   name="max_price",
     *   @OA\Schema(
     *     type="number",
     *     default="-1"
     *   ),
     *   in="query",
     *   required=false,
     *   description="상품 가격 최대값 검색 > -1 제한없음"
     *   ),
     *  @OA\Parameter(
     *   parameter="period",
     *   name="period",
     *   @OA\Schema(
     *     type="number",
     *     default="0"
     *   ),
     *   in="query",
     *   required=false,
     *   description="상품 등록기간 검색 0 무제한  1 3 7 day 기준"
     *   ),
     *  @OA\Parameter(
     *   parameter="compleate",
     *   name="compleate",
     *   @OA\Schema(
     *     type="number",
     *     default="0"
     *   ),
     *  @OA\Parameter(
     *   parameter="free item",
     *   name="is_free",
     *   @OA\Schema(
     *     type="number",
     *     default="0"
     *   ),
     *   in="query",
     *   required=false,
     *   description="거래완료 필터 0 없음 1 필터링"
     *  ),
     * )
     */
    public function search_list()
    {

        $input = $this->getRequestInputGetType($this->request);

        $top_input_search = $input["top_input_search"] ?? "";
        $search_location = $input["search_location"] ?? 0;
        $category = $input["category"] ?? -1;
        $min_price = $input["min_price"] ?? -1;
        $max_price = $input["max_price"] ?? -1;
        $latitude = $input["latitude"] ?? "";
        $longitude = $input["longitude"] ?? "";
        $period = $input["period"] ?? 0;
        $page = $input["page"] ?? 0;
        $compleate = $input["compleate"] ?? 0;
        $is_free = $input["is_free"] ?? null;

        //log_message(5, json_encode($input));

        $jwt_std = $this->init_jwt_info_pass();
        if ($jwt_std['code'] == 301) {
            //다시 체크해서 로그인 정보 가져옴
            $member_info = $this->init_jwt_info();
            $mem_id = $member_info['data']['member_id'];
        } else {
            $mem_id = 0; //없는 비로그인 맴버로 조회
        }

        $ProductModel = new ProductModel();
        $result = $ProductModel->product_list($top_input_search, $search_location, $page, $mem_id, $category, $min_price, $max_price, $latitude, $longitude, $period, $compleate, $is_free);

        if ($top_input_search) {
            $Redis = new Redis();
            $Redis->zadd($top_input_search);
        }

        $this->getResponseSend(["code" => 200, "message" => "search product list now", "page" => $page, "data" => $result], 200);
    }



    /**
     *  @OA\Post(
     *      tags={"Product"},
     *      path="/product/product_proc",
     *      security={{"bearerAuth": {}}},
     *      summary=" [상품등록]",
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
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      description="token PC는 session으로 처리 token 요청시 token 으로처리",
     *                      property="token",
     *                      type="string",     
     *                  ),     
     *                  @OA\Property(
     *                      description="상품이름",
     *                      property="product_name",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      description="상품설명",
     *                      property="content",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      description="offer 허용여부 1허용 0거절",
     *                      property="offeryn",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      description="상품가격",
     *                      property="price",
     *                      type="decimal",     
     *                  ),
     *                  @OA\Property(
     *                      description="cetegory 검색후 나온 id",
     *                      property="category_code_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      description="conditions 상품 상태 1 ~ 4",
     *                      property="conditions",
     *                      type="number",
     *                  ),
     *                 @OA\Property(
     *                  description="상품이미지 등록된 순서대로",
     *                  property="images[]",
     *                  type="array",
     *                  @OA\Items(
     *                       type="string",
     *                       format="binary",
     *                  ),
     *               ),
     *               @OA\Property(
     *                      description="선호지역명",
     *                      property="pf_location",
     *                      type="string",
     *                  ),
     *               @OA\Property(
     *                      description="선호 latitude",
     *                      property="pf_latitude",
     *                      type="number",
     *                  ),
     *               @OA\Property(
     *                      description="선호 longitude ",
     *                      property="pf_longitude",
     *                      type="number",
     *                  ),
     *              type="object",
     *              )
     *          )
     *      )
     *  )
     *  
     */
    public function product_proc()
    {

        $member_info = $this->init_jwt_info();
        $member_id = $member_info['data']['member_id'];

        $rules = [
            'product_name' => 'required',
            'price' => 'required',
            'offeryn' => 'required',
            'content' => 'required',
            'category_code_id' => 'required'
        ];

        $errors = [];
        $input = $this->getRequestInputPostType($this->request);

        if (!$this->validateRequest($input, $rules, $errors)) {
            log_message('notice', 'product_proc validateRequest ');
            return $this
                ->getResponse(
                    ["code" => 401, "message" => $this->validator->getErrors(), "data" => []],
                    200
                );
        }

        $ProductModel = new ProductModel();
        $myGeo = $ProductModel->getMyGeo($member_id);

        if ($myGeo["code"] != 200) {
            log_message('notice', 'product_proc geo err ');
            return $this
                ->getResponse(
                    ["code" => $myGeo["code"], "message" => $this->validator->getErrors(), "data" => []],
                    200
                );
        }

        $categoryCode = $ProductModel->getCategoryCode($input["category_code_id"]);
        if ($categoryCode["code"] != 200) {
            log_message('notice', 'product_proc category err ');
            return $this
                ->getResponse(
                    ["code" => $myGeo["code"], "message" => $this->validator->getErrors(), "data" => []],
                    200
                );
        }

        if (!isset($input["conditions"]) || !$input["conditions"]) $input["conditions"] = 1;
        $data = [
            "product_name" => $input["product_name"],
            "price" => $input["price"],
            "offer_price" => $input["price"],
            "category_code_id" => $input["category_code_id"],
            "product_latitude" => $myGeo["data"]['latitude'],
            "product_longitude" => $myGeo["data"]['longitude'],
            "station" => $myGeo["data"]['station1'] . ' ' . $myGeo["data"]['station2'],
            "mem_idx" => $member_id,
            "offer_yn" => $input["offeryn"],
            "content" => $input["content"],
            "status" => 1, //list open
            "created_tick" => time(),
            "conditions" => $input["conditions"] ?? 1
        ];

        //먼저 체크 이미지 업로드 갯수
        $thumbnail_upload_data = [];
        if ($imagefile = $this->request->getFiles()) {
            $i = 0;
            foreach ($imagefile['images'] as $img) {
                ++$i;

                if ($img->isValid() && !$img->hasMoved()) {
                    $newName = $img->getRandomName();
                    array_push($thumbnail_upload_data, ["img_url" => $newName]);
                    if ($i == 1) $first_name = $newName;
                    $img->move(ROOTPATH . 'public/uploads/', $newName);
                }
            }
        }

        if (count($thumbnail_upload_data) <= 0) {
            log_message('notice', ' thumbnail_upload_data err ! must 1 count ');
            log_message('error', json_encode($_FILES));
            return $this
                ->getResponse(
                    ["code" => ResponseInterface::HTTP_CONFLICT, "message" => $this->validator->getErrors(), "data" => []],
                    ResponseInterface::HTTP_OK
                );
        }


        $product_insert_id = $ProductModel->product_add($data);

        if (!$product_insert_id) {
            log_message('notice', 'product_proc insert err ');
            //insert err
            return $this->getResponseSend(["code" => ResponseInterface::HTTP_BAD_GATEWAY], 200);
            exit;
        }



        $thumbnail_data = [];
        foreach ($thumbnail_upload_data as $key => $val) {
            //size 480p
            // \Config\Services::image()
            // ->withFile(ROOTPATH . 'public/uploads/'.$val['img_url'])
            //->resize(720, 1280, false, 'height')
            // ->save(ROOTPATH . 'public/uploads/'.$val['img_url'], 50);
            // ->rotate(90)
            \Config\Services::image()
                ->withFile(ROOTPATH . 'public/uploads/' . $val['img_url'])
                ->reorient()
                // ->fit(720, 780, 'left')
                ->save(ROOTPATH . 'public/uploads/' . $val['img_url'], 70);

            //data
            $thumbnail_data[$key]['is_first']  = ($first_name  == $val['img_url']) ? 1 : 0;
            $thumbnail_data[$key]['img_url']  =  $val['img_url'];
            $thumbnail_data[$key]['product_id']  = $product_insert_id;
        }


        try {
            $credentials = new \Aws\Credentials\Credentials('AKIAXWHEKD7DMMG72TGI', 'VqXHcpX3JiiSCIWcwuyGADwyrn3WPl5tARpYb2oD');
            $s3  = new S3Client([
                'region' => 'ap-southeast-1',
                'version' => 'latest',
                'credentials' => $credentials
            ]);

            foreach ($thumbnail_data as $k => $v) {
                $key = 'product/' . $v['img_url'];
                $upload_file = $v['img_url'];

                $result = $s3->putObject([
                    'Bucket' => 'wevitt',
                    'Key'    => $key,
                    'Body'   => fopen(ROOTPATH . 'public/uploads/' . $upload_file, 'r'),
                    'ACL'    => 'public-read',
                ]);

                if ($result['@metadata']['statusCode'] == 200) {
                    unlink(ROOTPATH . 'public/uploads/' . $upload_file); //로컬파일 삭제
                    $thumbnail_data[$k]['img_url'] = $result['ObjectURL'];
                    //echo $result['ObjectURL'] . PHP_EOL;
                }
            }
        } catch (\Aws\S3\Exception\S3Exception $e) {
            log_message('notice', 'product_proc There was an error uploading the file err ');
            //echo "There was an error uploading the file.\n";
        }

        //썸네일
        $ProductModel->product_thumbnail_add($thumbnail_data);

        //선호위치 -- 위경도가 있으면 등록
        if (isset($input["pf_latitude"]) && isset($input["pf_longitude"])) {
            if ($input["pf_latitude"] && $input["pf_longitude"]) {
                $pf_data = [
                    "product_id" => $product_insert_id,
                    "pf_location" => $input["pf_location"],
                    "pf_latitude" => $input["pf_latitude"],
                    "pf_longitude" => $input["pf_longitude"],
                ];
                $ProductModel->product_preference($pf_data);
            }
        }




        log_message('notice', 'product_proc insert ok ');

        $data['product_id'] = $product_insert_id;
        $data['updated_at'] = date('Y-m-d H:i:s');
        $EsModel = new EsModel();
        $EsModel->put_product($data);

        log_message('notice', 'product_proc ES ok ');

        $fcm = new Fcm();
        $fcm->product_push($data['product_name'], $product_insert_id);
        log_message('notice', 'product_proc Fcm ok ');

        $this->getResponseSend(["code" => 200, "message" => "product list now ok", "product_id" => $product_insert_id], 200);
    }

    /**
     *  @OA\Post(
     *      tags={"Product"},
     *      path="/product/product_posts_proc",
     *      summary=" [상품 등록 posts 미등록]",
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
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      description="token PC는 session으로 처리 token 요청시 token 으로처리",
     *                      property="token",
     *                      type="string",     
     *                  ),     
     *                  @OA\Property(
     *                      description="상품이름",
     *                      property="product_name",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      description="상품설명",
     *                      property="content",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      description="offer 허용여부 1허용 0거절",
     *                      property="offeryn",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      description="상품가격",
     *                      property="price",
     *                      type="decimal",     
     *                  ),
     *                  @OA\Property(
     *                      description="cetegory 검색후 나온 id",
     *                      property="category_code_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      description="conditions 상품 상태 1 ~ 4",
     *                      property="conditions",
     *                      type="number",
     *                  ),
     *                 @OA\Property(
     *                  description="상품이미지 등록된 순서대로",
     *                  property="images[]",
     *                  type="array",
     *                  @OA\Items(
     *                       type="string",
     *                       format="binary",
     *                  ),
     *               ),
     *               @OA\Property(
     *                      description="선호지역명",
     *                      property="pf_location",
     *                      type="string",
     *                  ),
     *               @OA\Property(
     *                      description="선호 latitude",
     *                      property="pf_latitude",
     *                      type="number",
     *                  ),
     *               @OA\Property(
     *                      description="선호 longitude ",
     *                      property="pf_longitude",
     *                      type="number",
     *                  ),     
     *              type="object",
     *              )
     *          )
     *      )
     *  )
     *  
     */
    public function product_posts_proc()
    {

        $member_info = $this->init_jwt_info();
        $member_id = $member_info['data']['member_id'];
        $input = $this->getRequestInputPostType($this->request);
        $ProductModel = new ProductModel();
        $myGeo = $ProductModel->getMyGeo($member_id);

        if ($myGeo["code"] != 200) {
            log_message('notice', 'drafts geo err ');
            return $this
                ->getResponse(
                    ["code" => $myGeo["code"], "message" => "not geo err", "data" => []],
                    200
                );
        }
        $category_code_id = $input["category_code_id"] ?? null;
        if (!$category_code_id) $category_code_id = 194;

        $categoryCode = $ProductModel->getCategoryCode($category_code_id); //비어있으면 프리아이템
        if ($categoryCode["code"] != 200) {
            log_message('notice', 'drafts category err ');
            return $this
                ->getResponse(
                    ["code" => $myGeo["code"], "message" => "not category code", "data" => []],
                    200
                );
        }



        $data = [
            "product_name" => $input["product_name"],
            "price" => $input["price"],
            "offer_price" => $input["price"],
            "category_code_id" => $category_code_id,
            "product_latitude" => $myGeo["data"]['latitude'],
            "product_longitude" => $myGeo["data"]['longitude'],
            "station" => $myGeo["data"]['station1'] . ' ' . $myGeo["data"]['station2'],
            "mem_idx" => $member_id,
            "offer_yn" => $input["offeryn"],
            "content" => $input["content"],
            "status" => 0, //list close
            "created_tick" => time(),
            "conditions" => $input["conditions"] ?? 1
        ];

        $product_insert_id = $ProductModel->product_add($data);

        if (!$product_insert_id) {
            log_message('notice', 'drafts insert err ');
            //insert err
            return $this->getResponseSend(["code" => ResponseInterface::HTTP_BAD_GATEWAY], 200);
            exit;
        }

        $thumbnail_upload_data = [];
        if ($imagefile = $this->request->getFiles()) {
            $i = 0;
            foreach ($imagefile['images'] as $img) {
                ++$i;
                if ($img->isValid() && !$img->hasMoved()) {
                    $newName = $img->getRandomName();
                    array_push($thumbnail_upload_data, ["img_url" => $newName]);
                    if ($i == 1) $first_name = $newName;
                    $img->move(ROOTPATH . 'public/uploads/', $newName);
                }
            }
        }

        $thumbnail_data = [];
        foreach ($thumbnail_upload_data as $key => $val) {
            //size 480p
            \Config\Services::image()
                ->withFile(ROOTPATH . 'public/uploads/' . $val['img_url'])
                ->reorient()
                // ->fit(720, 780, 'left')
                ->save(ROOTPATH . 'public/uploads/' . $val['img_url'], 70);
            //data
            $thumbnail_data[$key]['is_first']  = ($first_name  == $val['img_url']) ? 1 : 0;
            $thumbnail_data[$key]['img_url']  =  $val['img_url'];
            $thumbnail_data[$key]['product_id']  = $product_insert_id;
        }

        try {
            $credentials = new \Aws\Credentials\Credentials('AKIAXWHEKD7DMMG72TGI', 'VqXHcpX3JiiSCIWcwuyGADwyrn3WPl5tARpYb2oD');
            $s3  = new S3Client([
                'region' => 'ap-southeast-1',
                'version' => 'latest',
                'credentials' => $credentials
            ]);

            foreach ($thumbnail_data as $k => $v) {
                $key = 'product/' . $v['img_url'];
                $upload_file = $v['img_url'];

                $result = $s3->putObject([
                    'Bucket' => 'wevitt',
                    'Key'    => $key,
                    'Body'   => fopen(ROOTPATH . 'public/uploads/' . $upload_file, 'r'),
                    'ACL'    => 'public-read',
                ]);

                if ($result['@metadata']['statusCode'] == 200) {
                    unlink(ROOTPATH . 'public/uploads/' . $upload_file); //로컬파일 삭제
                    $thumbnail_data[$k]['img_url'] = $result['ObjectURL'];
                }
            }
        } catch (\Aws\S3\Exception\S3Exception $e) {
            log_message('notice', 'drafts There was an error uploading the file err ');
        }

        $ProductModel->product_thumbnail_add($thumbnail_data);

        //선호위치 -- 위경도가 있으면 등록
        if (isset($input["pf_latitude"]) && isset($input["pf_longitude"])) {
            if ($input["pf_latitude"] && $input["pf_longitude"]) {
                $pf_data = [
                    "product_id" => $product_insert_id,
                    "pf_location" => $input["pf_location"],
                    "pf_latitude" => $input["pf_latitude"],
                    "pf_longitude" => $input["pf_longitude"],
                ];
                $ProductModel->product_preference($pf_data);
            }
        }

        log_message('notice', 'drafts insert ok ');

        $data['product_id'] = $product_insert_id;
        $data['updated_at'] = date('Y-m-d H:i:s');

        $EsModel = new EsModel();
        $EsModel->put_product($data);
        log_message('notice', 'drafts ES ok ');

        // 미노출 상품이라서 알림 메세지 안 보냄
        // $fcm = new Fcm();
        // $fcm->product_push($data['product_name'], $product_insert_id);
        // log_message('notice', 'drafts Fcm ok ');

        $this->getResponseSend(["code" => 200, "message" => "product list now ok", "product_id" => $product_insert_id], 200);
    }

    /**
     *  @OA\Post(
     *      tags={"Product"},
     *      path="/product/product_modify_proc",
     *      security={{"bearerAuth": {}}},
     *      summary=" [상품수정]",
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
     *              mediaType="multipart/form-data",
     *              @OA\Schema(     
     *                  @OA\Property(
     *                      description="상품번호",
     *                      property="product_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      description="상품이름",
     *                      property="product_name",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      description="상품설명",
     *                      property="content",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      description="offer 허용여부 1허용 0거절",
     *                      property="offeryn",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      description="상품가격",
     *                      property="price",
     *                      type="decimal",     
     *                  ),
     *                  @OA\Property(
     *                      description="cetegory 검색후 나온 id",
     *                      property="category_code_id",
     *                      type="number",
     *                  ),
     *                  @OA\Property(
     *                      description="conditions 상품 상태 1 ~ 4",
     *                      property="conditions",
     *                      type="number",
     *                  ),
     *                 @OA\Property(
     *                  description="상품이미지 등록된 순서대로 메인 이미지 삭제시 첫번째로 자동보정",
     *                  property="images[]",
     *                  type="array",
     *                  @OA\Items(
     *                       type="string",
     *                       format="binary",
     *                  ),         
     *               ),
     *               @OA\Property(
     *                      description="이미지 삭제  1,2,3,4",
     *                      property="imgaes_del",
     *                      type="string",
     *                  ),
     *               @OA\Property(
     *                      description="mode 디폴트 1 |  1 open change | 0 Drafts change ",
     *                      property="mode",
     *                      type="number",
     *                  ),
     *               @OA\Property(
     *                      description="선호지역명",
     *                      property="pf_location",
     *                      type="string",
     *                  ),
     *               @OA\Property(
     *                      description="선호 latitude",
     *                      property="pf_latitude",
     *                      type="number",
     *                  ),
     *               @OA\Property(
     *                      description="선호 longitude ",
     *                      property="pf_longitude",
     *                      type="number",
     *                  ),
     *              type="object",
     *              )
     *          )
     *      )
     *  )
     *  
     */
    public function product_modify_proc()
    {

        $member_info = $this->init_jwt_info();
        $member_id = $member_info['data']['member_id'];

        $rules = [
            'product_id' => 'required',
            'product_name' => 'required',
            'price' => 'required',
            'offeryn' => 'required',
            'content' => 'required',
            'category_code_id' => 'required'
        ];

        $errors = [];
        $input = $this->getRequestInputPostType($this->request);

        if (!$this->validateRequest($input, $rules, $errors)) {
            log_message('notice', 'product_modify_proc validateRequest ');
            return $this
                ->getResponse(
                    ["code" => 401, "message" => $this->validator->getErrors(), "data" => []],
                    200
                );
        }

        $ProductModel = new ProductModel();
        $myGeo = $ProductModel->getMyGeo($member_id);

        if ($myGeo["code"] != 200) {
            log_message('notice', 'product_proc geo err ');
            return $this
                ->getResponse(
                    ["code" => $myGeo["code"], "message" => $this->validator->getErrors(), "data" => []],
                    200
                );
        }

        $categoryCode = $ProductModel->getCategoryCode($input["category_code_id"]);
        if ($categoryCode["code"] != 200) {
            log_message('notice', 'product_proc category err ');
            return $this
                ->getResponse(
                    ["code" => $myGeo["code"], "message" => $this->validator->getErrors(), "data" => []],
                    200
                );
        }

        if (!isset($input["conditions"]) || !$input["conditions"]) $input["conditions"] = 1;
        $data = [
            "product_name" => $input["product_name"],
            "price" => $input["price"],
            "offer_price" => $input["price"],
            "category_code_id" => $input["category_code_id"],
            "product_latitude" => $myGeo["data"]['latitude'],
            "product_longitude" => $myGeo["data"]['longitude'],
            "station" => $myGeo["data"]['station1'] . ' ' . $myGeo["data"]['station2'],
            "mem_idx" => $member_id,
            "offer_yn" => $input["offeryn"],
            "content" => $input["content"],
            "status" => $input["mode"] ?? 1,
            "conditions" => $input["conditions"] ?? 1
        ];

        $product_id = $input['product_id'] ?? 0;
        $product_insert_id = $ProductModel->product_modify_proc($data, $product_id);

        if ($input["offeryn"] == 1) {
            $ProductModel->product_chat_offer_price_update($data, $product_id);
        }

        if (!$product_insert_id) {
            log_message('notice', 'product_proc update err ');
            //insert err
            return $this->getResponseSend(["code" => ResponseInterface::HTTP_BAD_GATEWAY], 200);
            exit;
        }

        //이미지 삭제부터 먼저 시작
        $imgaes_del = $input['imgaes_del'] ?? null;
        if ($imgaes_del) {
            $ProductModel->product_thumbnail_del(explode(',', $imgaes_del));
        }

        $thumbnail_upload_data = [];
        if ($imagefile = $this->request->getFiles()) {
            $i = 0;
            foreach ($imagefile['images'] as $img) {
                ++$i;
                if ($img->isValid() && !$img->hasMoved()) {
                    $newName = $img->getRandomName();
                    array_push($thumbnail_upload_data, ["img_url" => $newName]);
                    if ($i == 1) $first_name = $newName;
                    $img->move(ROOTPATH . 'public/uploads/', $newName);
                }
            }
        }

        $thumbnail_data = [];
        foreach ($thumbnail_upload_data as $key => $val) {
            //size 480p
            \Config\Services::image()
                ->withFile(ROOTPATH . 'public/uploads/' . $val['img_url'])
                ->reorient()
                // ->fit(720, 780, 'left')
                ->save(ROOTPATH . 'public/uploads/' . $val['img_url'], 70);
            //data
            $thumbnail_data[$key]['is_first']  = 0; //전부 0으로 셋팅
            $thumbnail_data[$key]['img_url']  =  $val['img_url'];
            $thumbnail_data[$key]['product_id']  = $product_insert_id;
        }

        try {
            $credentials = new \Aws\Credentials\Credentials('AKIAXWHEKD7DMMG72TGI', 'VqXHcpX3JiiSCIWcwuyGADwyrn3WPl5tARpYb2oD');
            $s3  = new S3Client([
                'region' => 'ap-southeast-1',
                'version' => 'latest',
                'credentials' => $credentials
            ]);

            foreach ($thumbnail_data as $k => $v) {
                $key = 'product/' . $v['img_url'];
                $upload_file = $v['img_url'];

                $result = $s3->putObject([
                    'Bucket' => 'wevitt',
                    'Key'    => $key,
                    'Body'   => fopen(ROOTPATH . 'public/uploads/' . $upload_file, 'r'),
                    'ACL'    => 'public-read',
                ]);

                if ($result['@metadata']['statusCode'] == 200) {
                    unlink(ROOTPATH . 'public/uploads/' . $upload_file); //로컬파일 삭제
                    $thumbnail_data[$k]['img_url'] = $result['ObjectURL'];
                }
            }
        } catch (\Aws\S3\Exception\S3Exception $e) {
            log_message('notice', 'product_proc There was an error uploading the file err ');
        }

        $ProductModel->product_thumbnail_add($thumbnail_data);
        $ProductModel->product_thumbnail_check($product_insert_id);

        //선호위치 -- 위경도가 있으면 등록
        if (isset($input["pf_latitude"]) && isset($input["pf_longitude"])) {
            if ($input["pf_latitude"] && $input["pf_longitude"]) {
                $pf_data = [
                    "product_id" => $product_insert_id,
                    "pf_location" => $input["pf_location"],
                    "pf_latitude" => $input["pf_latitude"],
                    "pf_longitude" => $input["pf_longitude"],
                ];
                $ProductModel->product_preference_modify($pf_data);
            }
        }

        log_message('notice', 'product edit proc insert ok ');

        $data['product_id'] = $product_insert_id;
        $data['updated_at'] = date('Y-m-d H:i:s');
        $EsModel = new EsModel();
        $EsModel->put_product($data);

        log_message('notice', 'product_proc ES ok ');

        $fcm = new Fcm();
        $fcm->product_push($data['product_name'], $product_insert_id);
        log_message('notice', 'product_proc Fcm ok ');

        $arr = ["code" => 200, "message" => "product list now ok", "product_id" => $product_insert_id];
        $this->getResponseSend($arr, 200);
    }


    /**
     *  @OA\Post(
     *      tags={"Product"},
     *      path="/product/order_offer_add",
     *      summary="make offer [오퍼 요청]",
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
     *                      description="요청 상품",
     *                      property="product_id",
     *                      type="number",     
     *                  ),
     *                  @OA\Property(
     *                      description="네고 요청 가격 [start chat은 0 으로 요청]",
     *                      property="offer_price",
     *                      type="number",     
     *                  ),
     *                  type="object",
     *              )
     *          )
     *      )
     *  )
     */
    public function order_offer_add()
    {

        $rules = [
            'product_id' => 'required',
        ];

        $errors = [
            'product_id' => [
                'validateUser' => 'Invalid product_id required'
            ],
        ];

        $input = $this->getRequestInputPostType($this->request);

        if (!$this->validateRequest($input, $rules, $errors)) {
            return $this
                ->getResponse(
                    $this->validator->getErrors(),
                    200
                );
        }

        $product_id = $input['product_id'] ?? 0;
        $offer_price = $input['offer_price'] ?? 0;

        $member_info = $this->init_jwt_info();
        $dealer_id = $member_info['data']['member_id']; //요청한 사람이 딜러
        $offer_hash_id = uniqid(); //구분값

        $ProductModel = new ProductModel();
        $offer_duplicate_check = $ProductModel->offer_duplicate_check($product_id, $dealer_id); //중복 오퍼인지 체크

        // if($offer_duplicate_check['code'] != 200 ) {
        //     //err
        //     $result["offer_hash_id"] = "";
        //     $result["code"] = 303;
        //     $result["message"] = "offer duplicate check err";
        //     $this->getResponseSend($result, 200);
        //     exit;
        // }

        if ($offer_duplicate_check['data']) {
            //duplicate offer
            //가격요청인 경우는 업데이트
            //log_message('info', json_encode($offer_duplicate_check));
            $ProductModel->offer_duplicate_price_update($product_id, $dealer_id, $offer_price);

            //판매자 재초대 전에 블럭 유저인지 체크
            $check_block = $ProductModel->check_block_user($product_id,  $dealer_id);
            if ($check_block['code'] == 200) {
                //블록되지 않은 경우만
                //판매자 재 초대
                $ProductModel->onner_offer_update($product_id,  $dealer_id);
            }

            $result["offer_hash_id"] = $offer_duplicate_check['data']['offer_hash_id'];
            $result["code"] = 200;
            $result["message"] = "offer duplicate";
            $this->getResponseSend($result, 200);
            exit;
        }

        $data = [
            'product_id' => $product_id,
            'offer_price' => $offer_price,
            'offer_hash_id' => $offer_hash_id,
            'dealer_id' => $dealer_id
        ];


        $offer_data = $ProductModel->offer_check($product_id, $offer_price); //주문한게 정상 상품인지 체크
        if ($offer_data["code"] != 200) {
            $this->getResponseSend($offer_data, 200);
            exit;
        }

        $result = $ProductModel->order_offer_add($data);
        $result["offer_hash_id"] = $offer_hash_id;

        //차단 유저 체크
        if ($result['code'] == 200) {
            $ProductModel->offer_ban_update($dealer_id, $product_id, $offer_hash_id);
        }
        $this->getResponseSend($result, 200);
    }


    /**
     *  @OA\Get(
     *      tags={"Product"},
     *      path="/product/product_detail_info",
     *      summary="product_detail_info",
     *      security={{"bearerAuth": {}}},
     *      @OA\Response(
     *          response=200,
     *          description="A list with location geo list"
     *      ),
     *      @OA\Response(
     *          response="default",
     *          description="an ""unexpected"" error"
     *      ),          
     * * @OA\Parameter(
     *   parameter="eventID_in_query",
     *   name="product_id",     
     *   @OA\Schema(
     *     type="string"
     *   ),
     *   in="query",
     *   required=false
     * ),
     *  )
     */
    public function product_detail_info()
    {

        $jwt_std = $this->init_jwt_info_pass();
        if ($jwt_std['code'] == 301) {
            //다시 체크해서 로그인 정보 가져옴
            $member_info = $this->init_jwt_info();
            $mem_id = $member_info['data']['member_id'];
        } else {
            $mem_id = 0; //없는 비로그인 맴버로 조회
        }

        $input = $this->getRequestInputGetType($this->request);
        $product_id = $input["product_id"] ?? "";
        $ProductModel = new ProductModel();
        $resut = $ProductModel->product_detail_info($product_id, $mem_id);
        $arr = ["code" => 200, "message" => "product detail info", "data" => $resut];
        $this->getResponseSend($arr, 200);
    }


    /**
     *  @OA\Post(
     *      tags={"Product"},
     *      path="/product/product_delete_proc",
     *      summary="delete product",
     *      security={{"bearerAuth": {}}},
     *      @OA\Response(
     *          response=200,
     *          description="A list with product unick view update "
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
     *                      description="id",
     *                      property="product_id",
     *                      type="number",     
     *                  ),
     *                  @OA\Property(
     *                      description="token 본인만 삭제 가능 혹은 관리자 직접삭제",
     *                      property="token",
     *                      type="string",
     *                  ),
     *                  type="object",
     *              )
     *          )
     *      )
     *  )
     */
    public function product_delete_proc()
    {
        $member_info = $this->init_jwt_info();
        $mem_id = $member_info['data']['member_id'];
        $input = $this->getRequestInputPostType($this->request);
        $product_id = $input["product_id"] ?? false;

        if ($product_id === false) {
            $arr = ["code" => 401, "message" => "not post product id", "data" => []];
            $this->getResponseSend($arr, 200);
            exit;
        }

        $ProductModel = new ProductModel();
        $result = $ProductModel->product_delete_proc($product_id, $mem_id);

        if ($result) {
            //검색엔진에서도 삭제
            $EsModel = new EsModel();
            $EsModel->delete_forced_product($product_id);
        }


        $arr = ["code" => 200, "message" => "product detail info", "data" => $result];
        $this->getResponseSend($arr, 200);
    }

    /**
     *  @OA\Post(
     *      tags={"Product"},
     *      path="/product/product_uv_update",
     *      summary="product_uv_update",
     *      security={{"bearerAuth": {}}},
     *      @OA\Response(
     *          response=200,
     *          description="A list with product unick view update "
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
     *                      description="id",
     *                      property="product_id",
     *                      type="number",     
     *                  ),
     *                  @OA\Property(
     *                      description="token > 값을 전달시 세션대체로 체크",
     *                      property="token",
     *                      type="string",
     *                  ),
     *                  type="object",
     *              )
     *          )
     *      )
     *  )
     */
    public function product_uv_update()
    {
        $member_info = $this->init_jwt_info();
        $input = $this->getRequestInputPostType($this->request);
        $product_id = $input["product_id"] ?? false;

        if ($product_id === false) {
            $arr = ["code" => 401, "message" => "not post product id", "data" => []];
            $this->getResponseSend($arr, 200);
            exit;
        }

        $ProductModel = new ProductModel();
        $resut = $ProductModel->product_uv_update($product_id);
        $arr = ["code" => 200, "message" => "product detail info", "data" => $resut];
        $this->getResponseSend($arr, 200);
    }

    /**
     *  @OA\Post(
     *      tags={"Product"},
     *      path="/product/product_wish_add",
     *      summary="product_wish_add",
     *      security={{"bearerAuth": {}}},
     *      @OA\Response(
     *          response=200,
     *          description="A list with product wish add "
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
     *                      description="id",
     *                      property="product_id",
     *                      type="number",     
     *                  ),
     *                  @OA\Property(
     *                      description="token > 값을 전달시 세션대체로 체크",
     *                      property="token",
     *                      type="string",
     *                  ),
     *                  type="object",
     *              )
     *          )
     *      )
     *  )
     */
    public function product_wish_add()
    {
        $member_info = $this->init_jwt_info();
        $input = $this->getRequestInputPostType($this->request);
        $product_id = $input["product_id"] ?? false;

        if ($product_id === false) {
            $arr = ["code" => 401, "message" => "not post product id", "data" => []];
            $this->getResponseSend($arr, 200);
            exit;
        }

        $ProductModel = new ProductModel();
        $resut = $ProductModel->product_wish_add($product_id, $member_info["data"]["member_id"]);
        $arr = ["code" => 200, "message" => "product detail info", "data" => $resut];
        $this->getResponseSend($arr, 200);
    }


    /**
     *  @OA\Post(
     *      tags={"Product"},
     *      path="/product/product_wish_del",
     *      summary="좋아요 즐겨찾기 삭제",
     *      security={{"bearerAuth": {}}},
     *      @OA\Response(
     *          response=200,
     *          description="A list with product wish delete "
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
     *                      description="id",
     *                      property="product_id",
     *                      type="number",     
     *                  ),
     *                  @OA\Property(
     *                      description="token > 값을 전달시 세션대체로 체크",
     *                      property="token",
     *                      type="string",
     *                  ),
     *                  type="object",
     *              )
     *          )
     *      )
     *  )
     */
    public function product_wish_del()
    {
        $member_info = $this->init_jwt_info();
        $input = $this->getRequestInputPostType($this->request);
        $product_id = $input["product_id"] ?? false;

        if ($product_id === false) {
            $arr = ["code" => 401, "message" => "not post product id", "data" => []];
            $this->getResponseSend($arr, 200);
            exit;
        }

        $ProductModel = new ProductModel();
        $resut = $ProductModel->product_wish_del($product_id, $member_info["data"]["member_id"]);
        $arr = ["code" => 200, "message" => "product detail info", "data" => $resut];
        $this->getResponseSend($arr, 200);
    }


    /**
     *  @OA\Post(
     *      tags={"Product"},
     *      path="/product/neighborhood_list",
     *      summary="동네 전체 리스트",
     *      @OA\Response(
     *          response=200,
     *          description="neighborhood list"
     *      ),
     *      @OA\Response(
     *          response="default",
     *          description="an ""unexpected"" error"
     *      ),
     *  )
     */
    public function neighborhood_list()
    {
        $ProductModel = new ProductModel();
        $result = $ProductModel->neighborhood_list();
        $arr = ["code" => 200, "message" => "neighborhood ALL list ", "data" => $result];
        $this->getResponseSend($arr, 200);
    }


    /**
     *  @OA\Get(
     *      tags={"Product"},
     *      path="/product/search_auto_complete",
     *      summary="키워드 자동완성",
     *      @OA\Response(
     *          response=200,
     *          description="A list with location geo list"
     *      ),
     *      @OA\Response(
     *          response="default",
     *          description="an ""unexpected"" error"
     *      ),          
     * * @OA\Parameter(
     *   parameter="eventID_in_query",
     *   name="keyword",     
     *   @OA\Schema(
     *     type="string",
     *     description="공백 기준으로 단어들이 여러개 검색됨" 
     *   ),
     *   in="query",
     *   required=false
     * ),
     *  )
     */
    public function search_auto_complete()
    {
        $input = $this->getRequestInputGetType($this->request);
        $keyword = $input['keyword'] ?? "";

        $keyword = explode(' ', $keyword);
        $serach_text = $keyword;
        //parse_str($keyword, $output);
        //$serach_text = implode( ',', $keyword );
        $EsModel  = new EsModel();
        $arr = $EsModel->search_auto_complete($serach_text);

        // echo '<pre>'; var_dump($arr); exit;

        // if(!is_array($arr)) {
        //     $arr = json_decode( $arr, true);
        // }
        //인덱스 정리
        $data = [];
        $i = 0;
        foreach ($arr as $key => $val) {
            $data[$i] = $val;
            $i++;
        }

        $this
            ->getResponseSend(
                ["code" => 200, "message" => "autocomplete", "data" => $data],
                ResponseInterface::HTTP_OK
            );
    }

    /**
     *  @OA\Post(
     *      tags={"Product"},
     *      path="/product/review_info",
     *      summary="상품 리뷰 정보",
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
     *                      description="product_id",
     *                      property="product_id",
     *                      type="number",     
     *                  ),
     
     *                  type="object",
     *              )
     *          )
     *      )
     *  )
     */
    public function review_info()
    {

        $input = $this->getRequestInputPostType($this->request);
        $product_id = $input['product_id'] ?? 0;
        $ProductModel = new ProductModel();
        $result = $ProductModel->review_info($product_id);
        $this->getResponseSend($result, 200);
    }

    /**
     *  @OA\Post(
     *      tags={"Product"},
     *      path="/product/review_info_id",
     *      summary="상품 리뷰 정보 id로 조회",
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
     *                      description="reviews_id",
     *                      property="reviews_id",
     *                      type="number",     
     *                  ),
     
     *                  type="object",
     *              )
     *          )
     *      )
     *  )
     */
    public function review_info_id()
    {

        $input = $this->getRequestInputPostType($this->request);
        $reviews_id = $input['reviews_id'] ?? 0;
        $ProductModel = new ProductModel();
        $result = $ProductModel->review_info_id($reviews_id);
        $this->getResponseSend($result, 200);
    }

    /**
     *  @OA\Post(
     *      tags={"Product"},
     *      path="/product/product_shares",
     *      summary="상품 쉐어",
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
     *                      description="product_id",
     *                      property="product_id",
     *                      type="number",     
     *                  ),     
     *                  type="object",
     *              )
     *          )
     *      )
     *  )
     */
    public function product_shares()
    {

        $input = $this->getRequestInputPostType($this->request);
        $product_id = $input['product_id'] ?? 0;
        $ProductModel = new ProductModel();
        $result = $ProductModel->product_shares($product_id);
        $this->getResponseSend($result, 200);
    }

    /**
     *  @OA\Post(
     *      tags={"Product"},
     *      path="/product/product_upstream",
     *      summary="상품 끌어올리기 72시간 1회",
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
     *                      description="product_id",
     *                      property="product_id",
     *                      type="number",
     *                  ),     
     *                  type="object",
     *              )
     *          )
     *      )
     *  )
     */
    public function product_upstream()
    {
        $member_info = $this->init_jwt_info();
        $mem_id = $member_info['data']['member_id'];
        $input = $this->getRequestInputPostType($this->request);
        $product_id = $input['product_id'] ?? 0;
        if (!$product_id) return $this->getResponse(['code' => 401, 'message' => 'not input product id', 'data' => []], 200);
        $ProductModel = new ProductModel();
        $result = $ProductModel->product_upstream($product_id, $mem_id);
        $this->getResponseSend($result, 200);
    }

    /**
     *  @OA\Post(
     *      tags={"Product"},
     *      path="/product/product_recommend",
     *      summary="상품 추천 리스트 [상품명]",     
     *      @OA\Response(
     *          response=200,
     *          description=""
     *      ),
     *      @OA\Response(
     *          response="default",
     *          description="an ""unexpected"" error"
     *      ),     
     *      )
     *  )
     */
    public function product_recommend()
    {
        $ProductModel = new ProductModel();
        $this->getResponseSend($ProductModel->product_recommend(), 200);
    }


    public function go_pick()
    {
        $input = $this->getRequestInputGetType($this->request);

        // 1등 
        // 2등
        // 3등
        // 꽝
        
        // 100000
        $rank[0] = 1;
        $rank[1] = 10;
        $rank[2] = 100;
        
        $max_rate = 10000000;
        
        $rand = mt_rand(1, $max_rate);
        
        $pick_number = -1;
        $pick = false;
        $plus_rate = 10;
        foreach($rank as $key => $val) {
            $val = $val + $plus_rate;
            if($val >= $rand) {
                $pick  = true;
                $pick_number = $key;
                break;
            }
        }

        $message = "";
        if($pick) {
            $message = "당첨";
        }else{
            $message =  "꽝";
        }
        


        
        $this->getResponseSend([ "code"=>200, "message"=> $message, "data"=>['number'=>$pick_number, 'pick'=>$pick] ], 200);
    }
}
