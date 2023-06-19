<?php

namespace App\Tablas;

use PDO;

class Articulo extends Modelo
{
    protected static string $tabla = 'articulos';

    public $id;
    private $codigo;
    private $descripcion;
    private $precio;
    private $stock;

    public function __construct(array $campos)
    {
        $this->id = $campos['id'];
        $this->codigo = $campos['codigo'];
        $this->descripcion = $campos['descripcion'];
        $this->precio = $campos['precio'];
        $this->stock = $campos['stock'];
    }

    public static function existe(int $id, ?PDO $pdo = null): bool
    {
        return static::obtener($id, $pdo) !== null;
    }

    public function getCodigo()
    {
        return $this->codigo;
    }

    public function getDescripcion()
    {
        return $this->descripcion;
    }

    public function getPrecio()
    {
        return $this->precio;
    }

    public function getPrecioCupon(?PDO $pdo = null)
    {
        $pdo = $pdo ?? conectar();
        $sent = $pdo->prepare("SELECT (a.precio - (a.precio * c.descuento)) AS precio FROM articulos a
                                                    JOIN articulos_cupones ac ON (a.id = ac.articulo_id)
                                                    JOIN cupones c ON (ac.cupon_id = c.id)
                                                    WHERE a.id = :id");
        $sent->execute([':id' => $this->id]);
        $this->precio = $sent->fetchColumn();

        return $this->precio;
    }


    public function getStock()
    {
        return $this->stock;
    }

    public function getId()
    {
        return $this->stock;
    }
}
