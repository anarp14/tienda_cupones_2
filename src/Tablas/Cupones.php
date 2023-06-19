<?php

namespace App\Tablas;

use PDO;

class Cupon extends Modelo
{
    protected static string $tabla = 'cupones';

    public $id;
    public $descuento;
    public $caducidad;
    public $cupon;
    private $codigo;

    public function __construct(array $campos)
    {
        $this->id = $campos['id'];
        $this->descuento = $campos['descuento'];
        $this->caducidad = $campos['caducidad'];
        $this->cupon = $campos['cupon'];
        $this->codigo = isset($campos['codigo']) ? $campos['codigo'] : null;
    }

    public static function existe(int $id, ?PDO $pdo = null): bool
    {
        return static::obtener($id, $pdo) !== null;
    }

    public function getId()
    {
        return $this->id;
    }
    
    public function getDescuento()
    {
        return $this->descuento;
    }

    public function getCaducidad()
    {
        return $this->caducidad;
    }

    public function getCupon()
    {
        return $this->cupon;
    }

    public function getCodigo()
    {
        $pdo = conectar();
        $sent = $pdo->prepare("SELECT codigo FROM articulos_cupones WHERE cupon_id = :id");
        $sent-> execute([':id' => $this->id]);
        $this->codigo = $sent->fetchColumn();
        return $this->codigo;
    }


}
