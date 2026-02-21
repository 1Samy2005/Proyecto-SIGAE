<?php
require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';

use App\Core\Database;

class HistoriaController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Muestra la historia académica de un estudiante
     */
    public function ver($id_estudiante) {
        // Obtener datos del estudiante
        $stmtEst = $this->db->query(
            "SELECT id_estudiante, cedula, nombres, apellidos,
                    fecha_nacimiento, lugar_nacimiento,
                    pais_nacimiento, estado_nacimiento, municipio_nacimiento
             FROM estudiantes WHERE id_estudiante = :id",
            ['id' => $id_estudiante]
        );
        $estudiante = $stmtEst->fetch();

        if (!$estudiante) {
            header('Location: /SIGAE/error/404');
            exit;
        }

        // Obtener historial de inscripciones
        $stmtIns = $this->db->query(
            "SELECT i.id_inscripcion, i.anio_escolar, s.nombre_seccion, i.estatus_inscripcion
             FROM inscripciones i
             JOIN secciones s ON i.id_seccion = s.id_seccion
             WHERE i.id_estudiante = :id_estudiante
             ORDER BY i.anio_escolar DESC",
            ['id_estudiante' => $id_estudiante]
        );
        $inscripciones = $stmtIns->fetchAll();

        // Cargar la vista
        require_once 'app/views/historia/index.php';
    }

    /**
     * Obtiene las calificaciones de un estudiante para un año específico (JSON)
     */
    public function getCalificacionesPorAnio($id_estudiante, $anio_escolar) {
        $stmt = $this->db->query(
            "SELECT 
                m.codigo_materia,
                m.nombre_materia,
                c.valor_calificacion,
                c.tipo_evaluacion
             FROM calificaciones c
             JOIN materias m ON c.id_materia = m.id_materia
             JOIN inscripciones i ON c.id_inscripcion = i.id_inscripcion
             WHERE i.id_estudiante = :id_estudiante 
               AND i.anio_escolar = :anio_escolar
             ORDER BY m.nombre_materia",
            ['id_estudiante' => $id_estudiante, 'anio_escolar' => $anio_escolar]
        );
        $calificaciones = $stmt->fetchAll();

        header('Content-Type: application/json');
        echo json_encode($calificaciones);
        exit;
    }
}
?>