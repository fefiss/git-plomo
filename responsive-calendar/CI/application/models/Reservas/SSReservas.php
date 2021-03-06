<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SSReservas
 *
 * @author LoLo
 */
class SSReservas extends CI_Model {

    private static $instancia;
    private $bd;

    public static function getInstancia() {
        if (!self::$instancia instanceof self) {
            self::$instancia = new self;
        }
        return self::$instancia;
    }

    public function __construct() {
        parent::__construct();
        $this->load->model('Reservas/HorariosBD');
        $this->load->model('Reservas/RegistroBD');
        $this->bd = new RegistroBD();
    }

    public function obtenerHorarios($pusuario, $fecha) {
        $ret = $this->bd->horasRegistradasXFecha($fecha, $pusuario); //tabla registro
        //¿Retorna null si la consuta es vacia?
        if ($ret == null) {
            $dia = date('N', strtotime($fecha));
            $ret = $this->bd->horariosAtencionXDia($dia, $pusuario);
        }
        return $ret;
    }

    public function ingresarHoraRegistrada($pregistro, $pusuario, $pnombre) {
        $ret = false;
        $registros = $this->bd->obtenerXFecha($pregistro->getFecha(), $pusuario, $pnombre);
        if ($registros != null) {
            for ($i = 0; $i < count($registros) && !$ret; $i++) {
                $r = $registros[i];
                if ($r->getTipo() == 0 && $r->getHorario()->estaEnHorario($pregistro->getHorario())) {
                    actualizarRegistrosXIngreso($r, $pregistro);
                    $ret = true;
                }
            }
        } else {
            $dia = date('N', strtotime($pregistro->getFecha()));
            $horarios = HorariosBD::getHorarios($dia, $pusuario, $pnombre);
            $nuevosRegistros = array();
            for ($i = 0; $i < count($horarios) && !$ret; $i++) {
                $h = $horarios[$i];
                $r = new Registro($pregistro->getFecha(), $h, 0);
                if ($h->estaEnHorario($pregistro->getHorario())) {
                    actualizarRegistrosXIngreso($r, $pregistro);
                    $ret = true;
                } else {
                    array_push($nuevosRegistros, $r);
                }
            }
            if ($ret) {
                foreach ($nuevosRegistros as $nuevo) {
                    $this->bd->insertarRgistro($nuevo);
                }
            }
            return $ret;
        }
    }

    private function actualizarRegistrosXIngreso($r, $nuevo) {
        if ($r->esIgual($nuevo)) {
            $r->setTipo($nuevo->getTipo());
        } else {
            if ($r->obtenerInicio() == $nuevo->obtenerInicio()) {
                $r->cambiarInicio($nuevo->obtenerFin());
            } else if ($r->obtenerFin() == $nuevo->obtenerFin()) {
                $r->cambiarFin($nuevo->obtenerInicio());
            } else {
                $horario = new Horario($nuevo->obtenerFin(), $r->obtenerFin());
                $nuevo2 = new Registro($r->getFecha(), $horario, 0);
                $r->cambiarFin($nuevo->obtenerInicio());
                $this->bd->insertarRgistro($nuevo2);
            }
            $this->bd->insertarRgistro($nuevo);
        }
        $this->bd->modificarRegistro($r);
    }

    //Descomentar todo
    public function ingresarHorarioAtencion($pregistro, $pnombre) {
        $ret = true;
        $dia = $pregistro->getFecha();
        $HorariosAtencion = $bd->horariosAtencionXDia($dia, $pregistro->getUsuario(), $pnombre);

        for ($i = 0; $i < count($HorariosAtencion) && $ret; $i++) {
            $h = $HorariosAtencion[$i];
            if ($h->getHorario()->horaEnHorario($pregistro->obtenerInicio()) ||
                    $h->getHorario()->horaEnHorario($pregistro->obtenerFin())) {
                $ret = false;
            }
        }
        if ($ret) {
            $bd->insertarHorarioAtencion($pregistro);
        }
        return $ret;
    }

    //RF.11 - Determinación horarios disponibles para un servicio
    public function getHorariosDisponibles() {
        $regBD = new RegistroBD();
        $SSReservas = new SSReservas();

        $ret = $regBD->setHorarios($pHoraIni, $pHoraFin, $pnombre);
    }

    //RF.11 - Determinación horarios disponibles para un servicio
//    public function setHorariosDisponibles() {
//        $ret = $this->bd->setHorarios($pHoraIni, $pHoraFin, $pnombre);
//        $this->load->model('Servicio');
//        $horarios = $SSReservas->obtenerHorario("0", 'sala 1', 1); //user, sala, dia
//        $data['horarios'] = $horarios;
//        $this->load->view("HorariosView", $data);
//    }

}
