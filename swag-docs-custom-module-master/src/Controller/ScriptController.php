<?php declare(strict_types=1);

namespace Swag\CustomModule\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @RouteScope(scopes={"api"})
 */
class ScriptController extends AbstractController
{
   
    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $productCrossSellingRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $productCrossSellingAssignedProductsRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $mediaRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $mediaFolderRepository;

    public function __construct(
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $productCrossSellingRepository,
        EntityRepositoryInterface $productCrossSellingAssignedProductsRepository,
        EntityRepositoryInterface $mediaRepository,
        EntityRepositoryInterface $mediaFolderRepository
    )
    {
        $this->productRepository = $productRepository;
        $this->productCrossSellingRepository = $productCrossSellingRepository;
        $this->productCrossSellingAssignedProductsRepository = $productCrossSellingAssignedProductsRepository;
        $this->mediaRepository = $mediaRepository;
        $this->mediaFolderRepository = $mediaFolderRepository;
    }


    /**
     * @Route("/api/v{version}/script/import", name="api.action.script.import", methods={"GET"})
     * @param Request $request
     * @param Context $context
     */
    public function importData(Request $request,Context $context): JsonResponse
    {
        // $servername = "localhost";
        // $username = "wisasanitair";
        // $password = "CLhTJ9BlmD5JlWxGT";
        // $dbname = "wisa_m1";

        // // Create connection
        // $conn = mysqli_connect($servername,$username,$password,$dbname);

        // $sql = "SELECT mghg_catalog_product_link.product_id,
        //                mghg_catalog_product_link.linked_product_id,
        //                mghg_catalog_product_entity.sku
        //         FROM mghg_catalog_product_link
        //         INNER JOIN mghg_catalog_product_entity ON mghg_catalog_product_link.product_id = mghg_catalog_product_entity.entity_id WHERE mghg_catalog_product_link.link_type_id = 1 ORDER BY mghg_catalog_product_link.product_id ASC";
        
        // $result = $conn->query($sql);

        // if ($result->num_rows > 0) {
            
        //     // output data of each row
        //     while($row = $result->fetch_assoc()) {
                
            
        //         $linkProduct = "SELECT sku FROM `mghg_catalog_product_entity` WHERE `entity_id` = ".$row["linked_product_id"]."  ORDER BY `sku` ASC";
        //         $linkProductresult = $conn->query($linkProduct);


        //         if ($linkProductresult->num_rows > 0) {
                    
        //             $linkProductrow = $linkProductresult->fetch_assoc();
                    
        //             $criteriaRelated = new Criteria();
        //             $criteriaRelated->addFilter(
        //                 new EqualsFilter('productNumber', $linkProductrow['sku'])
        //             );

        //             $productRelatedData = $this->productRepository->searchIds(
        //             $criteriaRelated,
        //             Context::createDefaultContext())->getIds();

        //             if(!empty($productRelatedData)) {

        //                 $criteria = new Criteria();
        //                 $criteria->addFilter(
        //                     new EqualsFilter('productNumber', $row["sku"])
        //                 );

        //                 $productDatas = $this->productRepository->searchIds(
        //                 $criteria,
        //                 Context::createDefaultContext())->getIds();

        //                 $productcrossDatas = $this->productCrossSellingRepository->searchIds(
        //                 (new Criteria())->addFilter(new EqualsFilter('productId', $productDatas[0])),
        //                 Context::createDefaultContext())->getIds();

        //                 if(empty($productcrossDatas)) {
                          
        //                     // Cross Selling insert
        //                     $crossSellingData = $this->productCrossSellingRepository->upsert(
        //                         [
        //                             [
        //                                 'name'              => 'Related Item',
        //                                 'type'              => 'productList',
        //                                 'position'          =>  1,
        //                                 'active'            =>  TRUE,
        //                                 'productId'         => $productDatas[0]

        //                             ]
        //                         ],
        //                         Context::createDefaultContext()
        //                     );

        //                     // Get inserted copy category ID
        //                     $crossSellingId = $crossSellingData->getEvents()->getElements()[0]->getids()[0];
        //                 }
        //                 else {
        //                     $crossSellingId = $productcrossDatas[0];
        //                 }


        //                 $productcrossassign = $this->productCrossSellingAssignedProductsRepository->searchIds(
        //                 (new Criteria())->addFilter(
        //                     new EqualsFilter('productId', $productRelatedData[0]),
        //                     new EqualsFilter('crossSellingId', $crossSellingId)
        //                 ),
        //                 Context::createDefaultContext())->getIds();

        //                 $productcrossassignCount = $this->productCrossSellingAssignedProductsRepository->searchIds(
        //                 (new Criteria())->addFilter(
        //                     new EqualsFilter('crossSellingId', $crossSellingId)
        //                 ),
        //                 Context::createDefaultContext())->getTotal();

        //                 if(!empty($productcrossassignCount))
        //                 {
        //                     $productcrossassignCount = $productcrossassignCount + 1;
        //                 }  
        //                 else
        //                 {
        //                     $productcrossassignCount = 1;
        //                 }
                    
        //                 $this->productCrossSellingAssignedProductsRepository->upsert(
        //                     [
        //                         [
        //                             'crossSellingId'    => $crossSellingId,
        //                             'productId'         => $productRelatedData[0],
        //                             'position'          => $productcrossassignCount,
        //                         ]
        //                     ],
        //                     Context::createDefaultContext()
        //                 );
        //             }
        //         }
        //     }
        // } 
        
        return new JsonResponse(['success']);
    }

    /**
     * @Route("/api/v{version}/script/attachment", name="api.action.script.attachment", methods={"GET"})
     * @param Request $request
     * @param Context $context
     */
    public function attachmentData(Request $request,Context $context): JsonResponse
    {
        // $products = $this->productRepository->search(
        //     (new Criteria())->addFilter(new EqualsFilter('ean', null))
        // , $context);
        // foreach ($products as $product) {
        //     foreach ($product->gettranslated()['customFields'] as $pkey => $pdata) {
        //         if (stripos($pkey, '_ean_140') !== false) {
                    
        //               $this->productRepository->upsert(
        //                         [
        //                             [
        //                                 'id'              => $product->getId(),
        //                                 'ean'              => $pdata
        //                             ]
        //                         ],
        //                         Context::createDefaultContext()
        //                     );
        //         }    
        //     } 
        // }

        $servername = "localhost";
        $username = "wisasanitair";
        $password = "CLhTJ9BlmD5JlWxGT";
        $dbname = "wisa_m1";

        // Create connection
        $conn = mysqli_connect($servername,$username,$password,$dbname);
        $sql = "SELECT name,store_ids,hex(media_id) as media FROM mghg_mageworx_downloads_files";
        $result = $conn->query($sql);
        
        $language = array();
        $language[1] = '2fbb5fe2e29a4d70aa5854ce7ce3e20b';
        $language[2] = '39172b837d8d49349e7326e8239a67de';
        $language[3] = 'f6287ca96ad9492db32f18a618ec78e1';
        $language[4] = '9df70900758c4e2da6bfc8613160aa01';
        $qerydata = "";
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $stores = explode(",", $row['store_ids']);
                foreach ($stores as $store) {
                    if($store != 0){

                    
                        // dd($context);
                        // $mediaID = $this->mediaRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('id', $row['media'])),Context::createDefaultContext())->getIds();
                        // if(!empty($mediaID)){
                        //     $this->mediaRepository->upsert(
                        //     [
                        //         [
                        //             'id'                    => $mediaID[0],
                        //             'title'                 => $row['name'],
                        //         ]
                        //     ],
                        //     Context::createDefaultContext()
                        //     );
                           
                        // }
                        // else {
                        //     echo $row['name'].' not updated';
                        // }

                        
                        $connection = $this->container->get(Connection::class);
                        $query = "SELECT id,hex(media_id) as media FROM  acris_product_download where HEX(media_id) = '".$row['media']."'";
                        $datas = $connection->executeQuery($query)->fetchAll();
                        

                        if(!empty($datas))
                        {
                            foreach ($datas as $data) {
                                $connection3 = $this->container->get(Connection::class);
                                $select = "SELECT COUNT(title) FROM `acris_product_download_translation` WHERE acris_product_download_id = '".$data['id']."' AND language_id = ".'0x'.$language[$store]."";
                                $selecteddata = $connection3->executeQuery($select)->fetch();
                                if($selecteddata['COUNT(title)'] == 0){

                                    $connection2 = $this->container->get(Connection::class);
                                    $insert2 = "INSERT INTO `acris_product_download_translation`(`title`, `description`, `acris_product_download_id`, `language_id`) VALUES ('".$row['name']."',null,'".$data['id']."',".'0x'.$language[$store].");";
                                
                                    $connection2->executeQuery($insert2);

                                    $connection3 = $this->container->get(Connection::class);
                                    $insert1 = "INSERT INTO `acris_product_download_language`(`download_id`, `language_id`) VALUES ('".$data['id']."',".'0x'.$language[$store].");";
                                    $connection3->executeQuery($insert1);
                                }
                                
                            }
                            // $connection2 = $this->container->get(Connection::class);
                            //  $insert2 = "INSERT INTO `media_translation`(`media_id`, `language_id`, `title`) VALUES ('".'0x'.$row['media']."','0xf6287ca96ad9492db32f18a618ec78e1','".$row['name']."');";
                            //  echo $insert2; die;
                            // $connection2->executeQuery($insert2); 

                        }
                        
                        //     foreach ($datas as $data) {

                        //         $res = $this->downloadTranslation($data['id'],$language[$store]);    
                        //         // // $select = "SELECT COUNT(title) FROM `acris_product_download_translation` WHERE acris_product_download_id = '".$data['id']."' AND language_id = ".'0x'.$language[$store]."";
                        //         // // $selecteddata = $connection2->executeQuery($select)->fetch();
                            
                        //         if($res['COUNT(download_id)'] == 0){

                        //         //     $connection2 = $this->container->get(Connection::class);
                        //         //     $insert2 = "INSERT INTO `acris_product_download_translation`(`title`, `description`, `acris_product_download_id`, `language_id`) VALUES ('".$row['name']."',null,'".$data['id']."',".'0x'.$language[$store].");";
                        //         //     $connection2->executeQuery($insert2); 
                        //         // }

                        //         $connection2 = $this->container->get(Connection::class);
                        //         $insert1 = "INSERT INTO `acris_product_download_language`(`download_id`, `language_id`) VALUES ('".$data['id']."',".'0x'.$language[$store].");";
                        //         $connection2->executeQuery($insert1);
                        //         }
                        //     }
                        // }


                    }
                }
               
            }
        }
        // $connection2 = $this->container->get(Connection::class);
        // $insert1 = "UPDATE `acris_product_download_translation` SET `language_id`='0x2fbb5fe2e29a4d70aa5854ce7ce3e20b' WHERE HEX(language_id) = '9df70900758c4e2da6bfc8613160aa01'";
        // $connection2->executeQuery($insert1);
        // return new JsonResponse(['success']);
        // $servername = "localhost";
        // $username = "wisasanitair";
        // $password = "CLhTJ9BlmD5JlWxGT";
        // $dbname = "wisa_m1";

        // // Create connection
        // $conn = mysqli_connect($servername,$username,$password,$dbname);

        // $sql = "SELECT 
        //         mghg_mageworx_downloads_categories.category_id,
        //         mghg_mageworx_downloads_categories.title as folder_name,
        //         mghg_mageworx_downloads_files.name,
        //         HEX(mghg_mageworx_downloads_files.media_id) media_id,
        //         mghg_mageworx_downloads_files.type,
        //         mghg_mageworx_downloads_files.filename,
        //         mghg_mageworx_downloads_relation.product_id,
        //         mghg_catalog_product_entity.sku
        //         FROM 
        //         mghg_mageworx_downloads_categories
        //         LEFT JOIN mghg_mageworx_downloads_files
        //         ON mghg_mageworx_downloads_categories.category_id = mghg_mageworx_downloads_files.category_id
        //         LEFT JOIN mghg_mageworx_downloads_relation
        //         ON mghg_mageworx_downloads_files.file_id = mghg_mageworx_downloads_relation.file_id 
        //         LEFT JOIN mghg_catalog_product_entity
        //         ON mghg_mageworx_downloads_relation.product_id = mghg_catalog_product_entity.entity_id

        //         where mghg_mageworx_downloads_relation.product_id != ''";
        
        // $result = $conn->query($sql);

        // if ($result->num_rows > 0) {
        //     $i = 0;
        //     $mediaName = array();
        //     while($row = $result->fetch_assoc()) {

        //         if ($row['folder_name'] == 'BIM bibliotheek' || $row['folder_name'] == 'BIM-Bibliothek')
        //         {
        //             $row['folder_name'] = 'BIM library';
        //         }  
        //         if ($row['folder_name'] == 'technisches Datenblatt' || $row['folder_name'] == 'technische fiche')
        //         {
        //             $row['folder_name'] = 'Technical data sheet';
        //         }  
        //         if ($row['folder_name'] == 'Documentación' || $row['folder_name'] == 'Documentatie' || $row['folder_name'] == 'Dokumentation')
        //         {
        //             $row['folder_name'] = 'Documentation';
        //         }


        //         // Get folder name using media folder repository
        //         $mediaFolderID = $this->mediaFolderRepository->searchIds(
        //         (new Criteria())->addFilter(
        //             new EqualsFilter('name', $row['folder_name'])
        //         ),
        //         Context::createDefaultContext())->getIds();
               
        //         if(!empty($mediaFolderID)) {

        //             // Get media table ID using file name
        //             $mediaID = $this->mediaRepository->searchIds(
        //             (new Criteria())->addFilter(
        //                 new EqualsFilter('id', $row['media_id'])
        //             ),
        //             Context::createDefaultContext())->getIds();
                    

        //             if(!empty($mediaID)){
        //                 // print_r($mediaID);
        //                 // echo $row['name'].'<br>';
        //                 // Update media table using media ID and update media folder ID

        //                 $this->mediaRepository->upsert(
        //                     [
        //                         [
        //                             'id'                    => $mediaID[0],
        //                             'mediaFolderId'         => $mediaFolderID[0],
        //                         ]
        //                     ],
        //                     Context::createDefaultContext()
        //                 );
                           
        //             }
        //             else {
        //                 echo $row['name'].' not updated';
        //             }
        //         }
        //     }
        //     exit;
        //     return new JsonResponse(['Attachment clicked']);
        // }
    }
    public function downloadTranslation($downloadid,$language_id)
    {
        $connection4 = $this->container->get(Connection::class);
        $select = "SELECT COUNT(download_id) FROM `acris_product_download_language` WHERE download_id = '".$downloadid."' AND language_id = ".'0x'.$language_id."";
        $selecteddata = $connection4->executeQuery($select)->fetch();
        return $selecteddata;  
    }
   


    


        //  $servername = "localhost";
        // $username = "wisasanitair";
        // $password = "CLhTJ9BlmD5JlWxGT";
        // $dbname = "wisa_m1";

        // // Create connection
        // $conn = mysqli_connect($servername,$username,$password,$dbname);
        // $sql = "SELECT name,store_ids,hex(media_id) as media FROM mghg_mageworx_downloads_files";
        // $result = $conn->query($sql);
        
        // $language = array();
        // $language[1] = '2fbb5fe2e29a4d70aa5854ce7ce3e20b';
        // $language[2] = '39172b837d8d49349e7326e8239a67de';
        // $language[3] = 'f6287ca96ad9492db32f18a618ec78e1';
        // $language[4] = '9df70900758c4e2da6bfc8613160aa01';
        // $qerydata = "";



        // if ($result->num_rows > 0) {
        //     while($row = $result->fetch_assoc()) {
        //         $stores = explode(",", $row['store_ids']);
        //         foreach ($stores as $store) {
        //             if($store != 0 || $store != 2 || $store != 3 || $store != 4){
        //                 $mediaID = $this->mediaRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('id', $row['media'])),Context::createDefaultContext())->getIds();
                        
        //                 if(!empty($mediaID)){
        //                     $this->mediaRepository->upsert(
        //                     [
        //                         [
        //                             'id'                    => $mediaID[0],
        //                             'title'                 => $row['name'],
        //                         ]
        //                     ],
        //                     $event->getContext()
        //                     );

                            
        //                 }

        //             }
        //         }
               
        //     }
        // }


}
