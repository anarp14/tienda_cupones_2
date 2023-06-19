<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/css/output.css" rel="stylesheet">
    <title>Comprar</title>
</head>

<body>
    <?php require '../vendor/autoload.php';

    if (!\App\Tablas\Usuario::esta_logueado()) {
        return redirigir_login();
    }

    $codigoCupon = obtener_post('codigoCupon');
    $errores = 0;
    $carrito = unserialize(carrito());

    if (isset($codigoCupon) && $codigoCupon != '') {
        $pdo = conectar();
        $sent = $pdo->prepare("SELECT c.*, ac.codigo AS codigo FROM cupones c
                                JOIN articulos_cupones ac ON (c.id = ac.cupon_id)
                                WHERE lower(unaccent(ac.codigo)) LIKE lower(unaccent(:codigoCupon))
                                 ");
        $sent->execute([':codigoCupon' => $codigoCupon]);

        $cupon = $sent->fetch(PDO::FETCH_ASSOC);
        
        $codigoCupon = $cupon['codigo'];
        if ($cupon) {   //Si el cupon se encuentra en la bd, la variable devuelve un array asociativo, en caso contrario, devuelve false.
            $cupon_id = $cupon['id'];
        }
        var_dump($cupon_id);
    }


        if (!empty($cupon)) {
            if ($cupon['caducidad'] < date("Y-m-d")) {
                $_SESSION['error'] = 'Código de cupón caducado';
                $errores += 1;
            }
        } else {
            $_SESSION['error'] = 'Código de cupón inválido.';
            $errores += 1;
        }

    

    if (obtener_post('_testigo') !== null) {
        $ids = implode(', ', $carrito->getIds());
        $where = "WHERE id IN ($ids)";
        $pdo = conectar();
        $sent = $pdo->query("SELECT *
                                 FROM articulos
                                $where");
        foreach ($sent->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            if ($fila['stock'] < $carrito->getLinea($fila['id'])->getCantidad()) {
                $_SESSION['error'] = 'No hay existencias suficientes para crear la factura.';
                return volver();
            }
        }

        // Crear factura
        $usuario = \App\Tablas\Usuario::logueado();
        $usuario_id = $usuario->id;

    
        $pdo->beginTransaction();

        $sent = $pdo->prepare('INSERT INTO facturas (usuario_id, cupon_id)
                               VALUES (:usuario_id, :cupon_id)
                               RETURNING id');
        $sent->execute([':usuario_id' => $usuario_id, ':cupon_id' => $cupon_id]);

        $factura_id = $sent->fetchColumn();
        $lineas = $carrito->getLineas();
        $values = [];
        $execute = [':f' => $factura_id];
        $i = 1;

        foreach ($lineas as $id => $linea) {
            $values[] = "(:a$i, :f, :c$i)";
            $execute[":a$i"] = $id;
            $execute[":c$i"] = $linea->getCantidad();
            $i++;
        }

        $values = implode(', ', $values);
        $sent = $pdo->prepare("INSERT INTO articulos_facturas (articulo_id, factura_id, cantidad)
                               VALUES $values");
        $sent->execute($execute);
        foreach ($lineas as $id => $linea) {
            $cantidad = $linea->getCantidad();
            $sent = $pdo->prepare('UPDATE articulos
                                      SET stock = stock - :cantidad
                                    WHERE id = :id');
            $sent->execute([':id' => $id, ':cantidad' => $cantidad]);
        }

        $pdo->commit();
        $_SESSION['exito'] = 'La factura se ha creado correctamente.';
        unset($_SESSION['carrito']);
        return volver();
    }

    ?>

    <div class="container mx-auto">
        <?php require '../src/_menu.php' ?>
        <?php require '../src/_alerts.php' ?>
        <div class="overflow-y-auto py-4 px-3 bg-gray-50 rounded dark:bg-gray-800">
            <form action="" method="post">
                <input type="text" name="codigoCupon" class="rounded-lg" value="<?= isset($cupon) && $cupon != '' ? $cupon['codigo'] : '' ?>">
                <button type="submit" href="" class="mx-auto focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-900"> Aplicar cupón </button>
            </form>
            <table class="mx-auto text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <th scope="col" class="py-3 px-6">Código</th>
                    <th scope="col" class="py-3 px-6">Descripción</th>
                    <th scope="col" class="py-3 px-6">Cantidad</th>
                    <th scope="col" class="py-3 px-6">Precio</th>
                    <th scope="col" class="py-3 px-6">Importe</th>
                </thead>
                <tbody>
                    <?php $total = 0 ?>
                    <?php foreach ($carrito->getLineas() as $id => $linea) : ?>
                        <?php
                        $articulo = $linea->getArticulo();
                        $idArticulo = $articulo->id;
                        $codigo = $articulo->getCodigo();
                        $cantidad = $linea->getCantidad();
                        $precio = $articulo->getPrecio();
                        $importe = $cantidad * $precio;
                        if (isset($cupon) && $errores == 0) {
                            $sent = $pdo->prepare("SELECT (a.precio - (a.precio * c.descuento)) AS precio FROM articulos a
                                                    JOIN articulos_cupones ac ON (a.id = ac.articulo_id)
                                                    JOIN cupones c ON (ac.cupon_id = c.id)
                                                    WHERE a.id = :id AND ac.codigo = :codigo");
                            $sent->execute([':id' => $idArticulo, ':codigo' => $codigoCupon]);
                            $precioCupon = $sent->fetchColumn();

                          
                         }

                         if ( isset($precioCupon) && $precioCupon != '') {
                            $importeCupon = $precioCupon * $cantidad;
                            $total += $importeCupon;
                        } else {
                            $total += $importe;
                        }
                        ?>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="py-4 px-6"><?= $articulo->getCodigo() ?></td>
                            <td class="py-4 px-6"><?= $articulo->getDescripcion() ?></td>
                            <td class="py-4 px-6 text-center"><?= $cantidad ?></td>
                            <td class="py-4 px-6 text-center">
                                <p class="<?= $precioCupon != '' ? 'text-red-600' : '' ?>" style="<?= isset($precioCupon) && $precioCupon != '' ? 'text-decoration: line-through' : '' ?>"><?= dinero($precio) ?></p>
                                <p class="<?= $precioCupon != '' ? 'text-green-700 font-bold' : '' ?>"><?= isset($precioCupon) &&  $precioCupon != '' ? dinero($precioCupon) : '' ?></p>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <?php if (isset($importeCupon)) : ?>
                                    <p class="text-red-600" style="text-decoration: line-through;"><?= dinero($importe) ?></p>
                                    <p class="text-green-700 font-bold"><?= dinero($importeCupon) ?></p>
                                <?php else : ?>
                                    <p><?= dinero($importe) ?></p>
                                <?php endif; ?>
                            </td>


                        </tr>
                    <?php endforeach ?>
                </tbody>
                <tfoot>
                    <td colspan="3"></td>
                    <td class="text-center font-semibold">TOTAL:</td>
                    <td class="text-center font-semibold"><?= dinero($total) ?></td>
                </tfoot>
            </table>
            <form action="" method="POST" class="mx-auto flex mt-4">
                <input type="hidden" name="_testigo" value="1">
                <button type="submit" href="" class="mx-auto focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-900">Realizar pedido</button>
            </form>
        </div>
    </div>
    <script src="/js/flowbite/flowbite.js"></script>
</body>

</html>