<?php

namespace App\Http\Controllers;

use Error;
use Carbon\Carbon;
use App\Models\Article;
use App\Models\Pedidos;
use App\Models\Pedidos_productos;
use App\Models\Admin_pedidos;
use App\Models\Admin_pedidos_productos;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    public function create(Request $request){
       // return "crear articulo con los atributos title :{$request->title} price : {$request->price}";
       try { return DB::transaction(function () use($request){
        $validation = Validator::make($request->all(),[
            'title' => 'required|string|unique:articles',
            'amount' => 'required|numeric',
            'price' => 'required|numeric'
        ],[
            'title.required' => 'el campo title es obligatorio para su creacion',
            'amount.required' => 'el campo amount es obligatorio para su creacion',
            'price.required' => 'el campo price es obligatorio para su creacion'
        ]);
        if($validation->fails()){
            return response($validation->errors(),400);
        }else{
            $article = new Article();
            $article->title = $request->title;
            $article->amount = $request->amount;
            $article->price = $request->price;
            $article->save();
            return response(['message' => 'consultado correctamente', 'data' => $article]);
        }
        },2);
        } catch (\Exception $e) {
            return response($e);
        }
    }

    public function list(){
        try {
            $article = Article::all();
            return response(['messsage' => 'consultado correctamente','data'=>$article]);

        } catch (\Exception $e) {
            return response($e);
        }
    }

    public function edit(Request $request,$id)
    {
        try {
            $validation = Validator::make($request->all(),[
                'title' => 'required|string|unique:articles',
                'amount' => 'required|numeric',
                'price' => 'required|numeric'
            ],[
                'title.required' => 'el campo title es obligatorio para su creacion',
                'amount.required' => 'el campo amount es obligatorio para su creacion',
                'price.required' => 'el campo price es obligatorio para su creacion'
            ]);

            if ($validation->fails()) {
                return response([$validation->errors()],400);
            }
        } catch (\Exception $e){
        return  response($e);
    }

    }
    public function delete($id){
        return "eliminar el articulo $id";
    }

    public function show($id){
        return "mostrar el articulo {$id}";
    }

    //ejercicio 17/4/2023
    public function ejercicio()
    {
       try {
        $cedis = DB::table('cedis as c')
            ->select('c.id','c.foto','c.nombre','c.idm_ciudad')
            ->selectRaw("(select count(*) from tiendas as t where estado = 5 and  t.id_cedis = c.id) as vendedores_activos" )
            ->selectRaw("(select count(*) from tiendas as t where estado != 5  and  t.id_cedis = c.id) as vendedores_inactivos" )
            ->selectRaw("(select count(*) from users_clientes as u Inner join tiendas as t on u.id_tienda = t.id where t.id_cedis = c.id ) as clientes ")
            ->selectRaw("(select count(*) from cedis_productos as cp where cp.id_cedis = c.id) as cedis_productos")
            ->orderBy('c.estado', 'desc')
            ->get();

        $productos = DB::table('productos_stocks as pt')
            ->select('pt.id','pt.cantidad_restante','pt.id_cedis','pt.id_producto')
            ->selectRaw("(select cp.valor*pt.cantidad_restante from  cedis_productos as cp  where  cp.id_producto = pt.id_producto  and pt.id_cedis = cp.id_cedis) as stock_producto")
            ->whereIn('pt.id_cedis',$cedis->pluck('cedi_id'))
            ->where('pt.cantidad_restante','>','0')
            ->get();

        //  obtener el array de ciudades
        DB::setDefaultConnection("mysql-dos");
        $ciudades = DB::table('u_ciudades')
        ->whereIn('id',$cedis->pluck('idm_ciudad'))
        ->get();

        $data_cedis =$cedis->map(function ($value) use($ciudades,$productos){
            $ciudad =$ciudades->where('id',$value->idm_ciudad)->first()->ciudad;
            return [
                'id_cedi'=> $value->cedi_id,
                'foto'=> $value->foto,
                'cadis_nombre' => $value->nombre,
                'ciudad' => $ciudad,
                'vendedores_activos'=>$value->vendedores_activos,
                'vendedores_inactivos'=>$value->vendedores_inactivos,
                'total_clientes'=>$value->clientes,
                'productos_total'=>$value->cedis_productos,
                'unidades_total'=> $productos->where('id_cedis',$value->cedi_id)->sum('cantidad_restante'),
                'valor_de_stock'=>$productos->where('id_cedis',$value->cedi_id)->sum('stock_producto')
            ];
        });

        $data = [
            'productos_total'=>$data_cedis->sum('productos'),
            'unidades_total'=>$data_cedis->sum('unidades'),
            'stock_total'=>$data_cedis->sum('valor_de_stock'),
            'cedis_data'=>$data_cedis
        ];
        DB::setDefaultConnection("mysql");

        return response(['message'=>'consultado correctamente', 'data'=>$data],200);
        } catch (\Exception $e){
        return response($e);
        }
       // ejemplo de cambio de base de datos
        // DB::setDefaultConnection("mysql-dos");
        // $segunda= DB::table('u_ciudades')
        // ->limit(10)
        // ->get();
        // $las_dos = $segunda;
        // DB::setDefaultConnection("mysql");
        // $primera = DB::table('cedis')
        // ->limit(10)
        // ->get();
        // $las_dos['otras']=$primera;
        // return $las_dos;
    }

        //ejercicio 2
    //tipo de salida de el calculo
    //1:semana
    //2:mes
    //3:año
    private function fecha_and_recurrente($element){
        if ($element == '1') {
            $now =  Carbon::now()->subWeek();
            $cantidad_recurrente = 1;
        }else if($element == '2'){
            $now =  Carbon::now()->subMonth();
            $cantidad_recurrente = 3;
        }else if ($element == '3'){
            $now = Carbon::now()->subYear();
            $cantidad_recurrente = 5;
        }
        $time = substr($now, 0, 10);
        return
        [
            'time' => $time,
            'recurrente' => $cantidad_recurrente
        ];

    }

    private function calculo($num1,$num2){
        if($num1>0 && $num2>0){
            return  ($num1*100)/$num2;
        }else{
            return 0;
        }
    }

    public function ejercicioDos(Request $request,$id){
        try {
            //de esta manera capturamos el id pasado por QueryP  a una variable
            $request['id'] = intval($id);

            $validation = Validator::make($request->all(),[
                'id'=>'required|exists:cedis,id',
                'formato_fecha' => 'required|numeric|max:3'
            ],[
                'formato_fecha.required' => 'el  tiempo_tipo campo es requerido',
                'formato_fecha.numeric' => 'el  tiempo_tipo debe ser  un numero',
            ]);

            if($validation->fails()){
                return response($validation->errors(),400);
            }else{

                $pedidos = DB::table('admin_pedidos')
                ->where('id_cedis',$id)
                ->get();

                // sacando el foprmato de fecha segun peticion de cliente

                $formato_fecha =self::fecha_and_recurrente($request->formato_fecha);
                $recurrente_num = $formato_fecha['recurrente'];
                $pedidos_por_fecha = count($pedidos->where('entrega_fecha','>=',$formato_fecha['time'] ));
                $ventas_por_fecha =$pedidos->where('entrega_fecha','>=',$formato_fecha['time'] )->sum('valor_final');
                $ids_vendedores =$pedidos->where('entrega_fecha','>=',$formato_fecha['time'] )->pluck('created_by')->unique()->values();
                $vendedores_por_fecha = count($ids_vendedores);
                $vendedores_total = count($pedidos->pluck('created_by')->unique()->values());

                $recurrentes = $ids_vendedores->map(
                    function($value)use($pedidos,$formato_fecha,$recurrente_num){
                        $cantidad_pedidos = count($pedidos->where('entrega_fecha','>=',$formato_fecha['time'])->where('created_by',$value));
                        if ($cantidad_pedidos>$recurrente_num) {
                            return [
                                'fiel'=>$value
                            ];
                        }
                    }
                );

                $vendedores_recurrentes = count($recurrentes->where('fiel','!=',null));
                    //facilitar el calculo de porcentajes

                //condicion para casos en que nos llegue 0 por variable de request
                if($ventas_por_fecha > 0 && $pedidos_por_fecha > 0) {
                    # code...
                    $tiket_por_fecha = $ventas_por_fecha/$pedidos_por_fecha;
                }else{
                    $tiket_por_fecha = 0;
                }
                if ($ventas_por_fecha >0 && $pedidos_por_fecha>0) {
                    $tiket_promedio= intval(substr($ventas_por_fecha/$pedidos_por_fecha,0,6));
                }else{
                    $tiket_promedio = 0;
                }
                if ($pedidos->sum('valor_final') >0 && count($pedidos)>0) {
                    $tiket_total = $pedidos->sum('valor_final')/count($pedidos);

                }else{
                    $tiket_total = 0;
                }

                $data = [
                    'id_cedi'=>$request['id'],
                    'cantidad_pedidos'=>$pedidos_por_fecha,
                    'pocentaje_pedidos_total'=>round(self::calculo($pedidos_por_fecha,count($pedidos)),2),
                    'cantidad_ventas'=>$ventas_por_fecha,
                    'porcentaje_ventas_total'=>round(self::calculo($ventas_por_fecha,$pedidos->sum('valor_final')),2),
                    'tiket_promedio'=>$tiket_promedio,
                    'porcentaje_tiket_promedio_total'=>round(self::calculo($tiket_por_fecha,$tiket_total)),
                    'cantidad_vendedores'=>$vendedores_por_fecha,
                    'vendedores_promedio_total'=>round(self::calculo($vendedores_por_fecha,$vendedores_total),2),
                    'vendedores_recurrentes'=>$vendedores_recurrentes,
                    'vendedores_recurrentes_promedio_total'=>round(self::calculo($vendedores_recurrentes,$vendedores_total),2),
                    'vendedores_no_recurrentes'=>$vendedores_por_fecha-$vendedores_recurrentes,
                    'vendedores_no_recurrentes_promedio_total'=>round(self::calculo($vendedores_por_fecha-$vendedores_recurrentes,$vendedores_total),2)
                ];

                return response(['message'=>'consultado correctamente','data'=> $data]);
            }
        } catch (\Exception $e) {
            return response($e);
        }
    }
    private function register_productos()
    {

    }
    //ejercicio 3
    public function venta(Request $request){
        try {
            return DB::transaction(function ()use($request) {

                $validacion = Validator::make($request->all(),[
                    'id_tienda'=>'required|numeric|exists:tiendas,id',
                    'entrega_fecha'=>'required|date|after:yesterday',
                    'productos'=>'required|array',
                    'productos.*.id_producto'=>'required|numeric|exists:productos,id',
                    'productos.*.cantidad'=>'required|numeric',

                ],[
                    //personalizando mensajes de validaciones
                    'required'=>'el campo :attribute es obligatorio',
                    'numeric'=>'el campo :attribute debe ser un numero',
                    'exists'=>'no hay :attribute con id ',
                    'date'=>':attribute debe ser una fecha pormato ejemplo (2023-04-20)',
                    'after'=>'no se puede solicitar un pedido con fecha de entrega anterior a hoy',
                ],);
                if($validacion->fails()){
                    return response($validacion->errors(),400);
                }
                    $tienda= DB::table('tiendas')
                    ->where('id',$request->id_tienda)
                    ->first();

                    $ids_productos_consultados= DB::table('cedis_productos')
                    ->where('id_cedis',$tienda->id_cedis)
                    ->get();

                    $productos = collect($request->productos);
                    $id_no_valido = $productos->whereNotIn('id_producto',$ids_productos_consultados->pluck('id_producto'))->pluck('id_producto');
                    $valor_productos = $ids_productos_consultados->whereIn('id_producto',$productos->pluck('id_producto'))->values();

                    //validacione por casos de uso
                    //1: si el id solicitado existe en el cedi solicitado segun tienda
                    if (count($id_no_valido)>0) {
                        return response(['message' => "productos no existentes",$id_no_valido]);
                    }

                    $suma_por_producto = $productos->map(function($value) use($valor_productos){
                        return $valor_productos->where('id_producto',$value['id_producto'])->first()->valor * intval($value['cantidad']);
                    });

                    //validacion de que si el pedido solicitado cumple con la compra minima de la tienda

                    if ($suma_por_producto->sum() < $tienda->pedido_minimo) {
                        return response(['message' => "compra minima de la tienda es de $tienda->pedido_minimo"]);
                    }


                   // crteando instancia incial de un perdido
                    $pedido = new Pedidos();
                    $pedido->estado = 1 ;
                    $pedido->entrega_fecha = $request->entrega_fecha;
                    $pedido->direccion = 'calle 1 su # 32 35 edificio casa real';
                    $pedido->entrega_horario = 7 ; //hora de entrega establecida
                    $pedido->valor_costo = 0;
                    $pedido->valor_productos = 0;
                    $pedido->valor_envio = 0;
                    $pedido->valor_descuento = 0;
                    $pedido->valor_final = 0;
                    $pedido->distancia = 0;
                    $pedido->idm_moneda = 1; //peso colombiano
                    $pedido->id_tienda = $request->id_tienda;
                    $pedido->longitud = 100000;
                    $pedido->latitud = 100000;
                    $pedido->created_by = 1;//este usuario es admin gbp
                    $pedido->save();


                    //registro producto a producto del pedido

                    $registro_productos =  $productos->map(function($value) use($ids_productos_consultados,$pedido){
                        $producto = $ids_productos_consultados->where('id_producto',$value['id_producto'])->first();
                        $pedido_producto = new Pedidos_productos();
                        $pedido_producto->id_producto = $value['id_producto'];
                        $pedido_producto->estado =11;//creado original
                        $pedido_producto->cantidad = $value['cantidad'];
                        $pedido_producto->unidad_costo =$producto->valor;
                        $pedido_producto->unidad_teorica=$pedido_producto->unidad_costo ;

                        //condicion que nos permite asignar el valor final teniendo en cuenta si este tiene promo
                        if ( !is_null($producto->id_promocion)) {
                            $pedido_producto->unidad_final=$producto->promo_valor;
                            $pedido_producto->total_final =   $producto->promo_valor *  $value['cantidad'];
                            $pedido_producto->promocion = 1;
                        }else {
                            $pedido_producto->unidad_final =$producto->valor ;
                            $pedido_producto->total_final =   $producto->valor *  $value['cantidad'];
                            $pedido_producto->promocion = 0;
                        }
                        $pedido_producto->impuesto = 0;
                        $pedido_producto->total_costo =   $producto->valor * $value['cantidad'] ;
                        $pedido_producto->total_teorico = $producto->valor * $value['cantidad'] ;
                        $pedido_producto->id_pedido =$pedido->id;
                        $pedido_producto->save();

                        return $pedido_producto;
                    });


                    //calculo precio domicilio
                    if($tienda->domicilio_gratis < $registro_productos->sum('total_final')) {
                        $valor_envio = 5000;
                    }else {
                        $valor_envio = 0;
                    };

                     $pedido_valor_final =$registro_productos->sum('total_teorico') + $valor_envio - ($registro_productos->sum('total_teorico')-$registro_productos->sum('total_final'));



                    //actualizar pata entregar los datos totales
                    $pedido_actualizado = Pedidos::where('id',$pedido->id)
                    ->update([
                        'estado' => 2,
                        'valor_costo'=> $registro_productos->sum('total_costo'),
                        'valor_productos'=>$registro_productos->sum('total_teorico'),
                        'valor_descuento'=>($registro_productos->sum('total_teorico'))-($registro_productos->sum('total_final')),
                        //condicion para sacar el valor de envio segun la tabla tiendad->domicilio_gratis
                        'valor_envio'=>$valor_envio,
                        'valor_final'=> $pedido_valor_final
                    ]);


                   // ahora empezamos con la parte de  cara al vendedor y el cedi


                   //intancia de el pedido
                    $admin_pedidos = new Admin_pedidos();
                    $admin_pedidos->id_pedido = $pedido->id;
                    $admin_pedidos->entrega_fecha = Carbon::parse($request->entrega_fecha)->format('Y-m-d');
                    $admin_pedidos->entrega_horario = 7;
                    $admin_pedidos->estado = 1;
                    $admin_pedidos->direccion = 'calle 1 su # 32 35 edificio casa real';
                    $admin_pedidos->longitud = 10000;
                    $admin_pedidos->latitud = 10000;
                    $admin_pedidos->valor_costo = 0;
                    $admin_pedidos->valor_productos = 0;
                    $admin_pedidos->valor_envio = 0;
                    $admin_pedidos->valor_descuento = 0;
                    $admin_pedidos->valor_final = 0;
                    $admin_pedidos->distancia = 30;
                    $admin_pedidos->id_cedis = $tienda->id_cedis;
                    $admin_pedidos->idm_moneda = 1;
                    $admin_pedidos->created_by = $tienda->created_by;
                    $admin_pedidos->save();


                    //registro producto a producto
                    $registro_admin_productos =  collect($request->productos)->map(function($value) use($ids_productos_consultados,$admin_pedidos){
                        $producto = $ids_productos_consultados->where('id_producto',$value['id_producto'])->first();

                        $admin_pedido_productos = new Admin_pedidos_productos();
                        $admin_pedido_productos->id_producto = $value['id_producto'];
                        $admin_pedido_productos->cantidad = $value['cantidad'];
                        $admin_pedido_productos->unidad_costo =$producto->valor;
                        $admin_pedido_productos->unidad_teorica=$admin_pedido_productos->unidad_costo ;

                        if ( !is_null($producto->id_promocion)) {
                            $admin_pedido_productos->unidad_final=$producto->promo_valor;
                            $admin_pedido_productos->total_final =  $producto->promo_valor * $value['cantidad'];
                            $admin_pedido_productos->promocion = 1;

                        }else {
                            $admin_pedido_productos->unidad_final = $producto->valor ;
                            $admin_pedido_productos->total_final =  $producto->valor * $value['cantidad'];
                            $admin_pedido_productos->promocion = 0;
                        }
                        $admin_pedido_productos->impuesto = 0;
                        $admin_pedido_productos->total_costo = $producto->valor * $value['cantidad'] ;
                        $admin_pedido_productos->total_teorico = $producto->valor * $value['cantidad']  ;
                        $admin_pedido_productos->id_admin_pedido =$admin_pedidos->id;
                        $admin_pedido_productos->save();

                        return $admin_pedido_productos;

                    });
                    $pago_productos=$registro_admin_productos->sum('total_final');

                    $condicion_tienda = DB::table('tiendas_condiciones_pagos')
                    ->where('id_cedis',$tienda->id_cedis)
                    ->where('id_condicion',$tienda->id_condicion)
                    ->where('desde','<=',$pago_productos)
                    ->where('hasta','>',$pago_productos)
                    ->first();



                    //sacamos el valor a pagar dela condicion que trae cada tienda

                    //if por si llega 0 en la coindicion_pago
                    if ( !is_null($condicion_tienda->pago) )  {
                        $pago_condicion  = ($pago_productos * $condicion_tienda->pago)/100  ;
                    }else{
                        $pago_condicion = 0;
                    }

                    $valor_descuento=($registro_admin_productos->sum('total_teorico'))-($registro_admin_productos->sum('total_final'));

                    //actualizacion ara entregar los totales
                    Admin_pedidos::where('id',$admin_pedidos->id)->update([
                        'estado' => 2,
                        'valor_costo'=>$pago_productos,
                        'valor_productos'=>$pago_productos,
                        'valor_descuento'=>$valor_descuento,
                        'valor_envio'=>$valor_envio,
                        'id_condicion'=>$tienda->id_condicion,
                        'valor_condicion'=>$pago_condicion,
                        'valor_final'=>$pago_productos+$valor_envio-$valor_descuento-$pago_condicion,
                    ]);

                    return response(['message'=> 'creado correctamente', 'data'=>Admin_pedidos::where('id',$admin_pedidos->id)->first()],200);

            },2);
        } catch (\Exception $e) {
            return response($e);
        }

    }

}
