<?php



use Doctrine\DBAL\DriverManager;



class Export{


    private $conn;


    public function __construct(){

        /**
        * conncet to database
        */

        $connectionParams = [
            'dbname' => DBNAME,
            'user' => DBUSER,
            'password' => DBPASSWORD,
            'host' => DBHOST,
            'driver' => DBDRIVER,
            'charset' => DBCHARSET,
        ];
        $this->conn = DriverManager::getConnection($connectionParams);

    }




    public function __destruct(){

        /**
        * close database connection
        */
        $this->conn->close();

    }





    /**
    * build products xml
    */
    public function exportXML(){

        //xml header
        $xml = $this->getXMLHeader();

        //products tp xml
        $xml .= '<products>' . $this->buildProductsXML() . '</products>';

        return $xml;

    }



    /**
    * xml header
    */
    private function getXMLHeader(){
        return '<?xml version="1.0" encoding="UTF-8"?>';
    }



    private function buildProductsXML(){

        //products xml var
        $productXML = '';

        //query products
        $result = $this->conn->createQueryBuilder()
                            ->select( 'p.name' )
                            ->from( 'products', 'p' )
                            ->setMaxResults( 50 )
                            ->fetchFirstColumn();

        //products one by one
        foreach( $result as $name ){

            $productXML .=  '<product>' .
                                $this->getXMLTitle( (string)$name ) .  //title
                                $this->getXMLPrice( (string)$name ) .  //price
                                $this->getXMLCategories( (string)$name ) .  //categories
                            '</product>';

        }

        //return products queried
        return $productXML;

    }




    /**
    * title tag
    */
    private function getXMLTitle( $name ){
        return '<title>' . $name . '</title>';
    }



    /**
    * price tag
    */
    private function getXMLPrice( $name ){

        //query latest price
        $result = $this->conn->createQueryBuilder()
                            ->select( 'ph.price' )
                            ->from( 'price_history', 'ph' )
                            ->where( 'ph.product_name = :product_name' )
                            ->setParameter( 'product_name', $name )
                            ->orderBy( 'updated_at', 'desc' )
                            ->fetchOne();

        //return latest price
        return '<price>' . (int)$result . '</price>';

    }




    /**
    * build categories xml container
    */
    private function getXMLCategories( $name ){

        //categories xml var
        $categoriesXML = '';

        //query categories for the product
        $result = $this->conn->createQueryBuilder()
                            ->select( 'pc.product_category' )
                            ->from( 'product_categories', 'pc' )
                            ->where( 'pc.product_name = :product_name' )
                            ->setParameter( 'product_name', $name )
                            ->orderBy( 'product_category', 'asc' )
                            ->fetchFirstColumn();

        //categories for the product one by one
        foreach( $result as $category ){
            $categoriesXML .= '<category>' . (string)$category . '</category>';
        }

        return '<categories>' . $categoriesXML . '</categories>';

    }

}




?>