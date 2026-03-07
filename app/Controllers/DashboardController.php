<?php
// =====================================================
// EduSaaS RD - DashboardController
// =====================================================

class DashboardController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $this->requireSuscripcion();

        // Si el super admin está en modo visor, aplicar restricciones
        if (($_SESSION['rol_id'] ?? 0) === ROL_SUPER_ADMIN && VisorMiddleware::estaActivo()) {
            VisorMiddleware::aplicar();
        } elseif (($_SESSION['rol_id'] ?? 0) === ROL_SUPER_ADMIN && !VisorMiddleware::estaActivo()) {
            // Super admin sin modo visor → su panel
            $this->redirect('/superadmin');
            return;
        }

        $db            = Database::getInstance();
        $institucionId = $this->getInstitucionId();

        // $institucionId = NULL  → super admin, consultas globales
        // $institucionId = int   → admin/profesor, filtra por su institución

        // --------------------------------------------------
        // Estadísticas — helper privado para no repetir lógica
        // --------------------------------------------------
        $stats = [];

        // Total estudiantes activos
        if ($institucionId) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM estudiantes WHERE institucion_id = :id AND activo = 1");
            $stmt->execute([':id' => $institucionId]);
        } else {
            $stmt = $db->query("SELECT COUNT(*) FROM estudiantes WHERE activo = 1");
        }
        $stats['estudiantes'] = (int) $stmt->fetchColumn();

        // Total profesores activos
        if ($institucionId) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM profesores WHERE institucion_id = :id AND activo = 1");
            $stmt->execute([':id' => $institucionId]);
        } else {
            $stmt = $db->query("SELECT COUNT(*) FROM profesores WHERE activo = 1");
        }
        $stats['profesores'] = (int) $stmt->fetchColumn();

        // Cuotas pendientes
        if ($institucionId) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM cuotas WHERE institucion_id = :id AND estado = 'pendiente'");
            $stmt->execute([':id' => $institucionId]);
        } else {
            $stmt = $db->query("SELECT COUNT(*) FROM cuotas WHERE estado = 'pendiente'");
        }
        $stats['cuotas_pendientes'] = (int) $stmt->fetchColumn();

        // Ausentes hoy
        $hoy = date('Y-m-d');
        if ($institucionId) {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM asistencias a
                 INNER JOIN matriculas m ON a.matricula_id = m.id
                 WHERE m.institucion_id = :id AND a.fecha = :hoy AND a.estado = 'ausente'"
            );
            $stmt->execute([':id' => $institucionId, ':hoy' => $hoy]);
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM asistencias WHERE fecha = :hoy AND estado = 'ausente'");
            $stmt->execute([':hoy' => $hoy]);
        }
        $stats['ausentes_hoy'] = (int) $stmt->fetchColumn();

        $this->render('dashboard/index', ['stats' => $stats], 'Dashboard');
    }
}