<?php

include('../../classes/Barcode2.php');
include('../../classes/Utiles.php');
include('../../classes/Preparaxml.php');


$data = [
     'cod_doc'       => '01', // Factura
     'fecha'         => 'Y-m-d H:m:s',
     'total'         => '205.60',
     'impuesto'      => '18',
     'num_serie'     => 'FE01',
     'num_documento' => '0001',
     'moneda'        => '1',
     'razon_social'  => 'MI EMPRESA',
     'ruc'           => '123456789',
 ];

$items = [
	[
		['id_producto'] => '122',
		['codigo_producto'] => 'RG1004'
		['medida_producto'] => 'UNIDAD',
		['cantidad'] => '4',
		['nombre_producto'] => 'REGLA DE ALUMINIO ECONOMICA 3 1/4"x 1 1/2" x 5.95m (80 x 35mm)',
		['precio_venta_f'] => '43.56',
		['precio_total_f'] => '174.24'
	]
	
];

//ARMAR EL XML
$preparaxml = new Preparaxml();

$preparaxml->cargadataFactura($data,$items);

$preparaxml->generarArchivo('facturas',substr($data['fecha'],0,10),'{{RUC}}-01-'.$num_serie.'-'.$num_documento);

$datos = $preparaxml->firmar('{{RUC}}','facturas',substr($data['fecha'],0,10),'{{RUC}}-01-'.$num_serie.'-'.$num_documento);

//para el barcode
$barcode = new Barcode2();
$barcode->setData('{{RUC}}|'.$data['cod_doc'].'|'.$num_serie.'|'.$num_documento.'|'.number_format($data['total']-$data['total']/1.18,2).'|'.number_format($data['total'],2,'.','').'|'.date('d/m/Y').'|1|'.$data['ruc'].'|'.$datos['DigestValue'].'|'.$datos['SignatureValue']);
$a = $barcode->getgd();
file_put_contents('../../barcodes/'.substr($data['fecha'],0,10).$num_serie.'-'.$num_documento.'.png', $a);

