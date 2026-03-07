<?php
// =====================================================
// EduSaaS RD - SuscripcionMiddleware
// Verifica suscripción activa y carga el plan completo
// en $_SESSION['plan'] para que PlanHelper lo use.
// El Super Admin siempre pasa sin restricciones.
// =====================================================

class SuscripcionMiddleware
{
    /**
     * Punto de entrada del middleware.
     * Llamar al inicio de CADA método de controladores del colegio.
     *
     *   SuscripcionMiddleware::verificar();
     */
    public static function verificar(): void
    {
        // Super Admin nunca es bloqueado — carga plan vacío
        if (($_SESSION['rol_id'] ?? 0) === ROL_SUPER_ADMIN) {
            if (empty($_SESSION['plan'])) {
                $_SESSION['plan'] = self::planIlimitado();
            }
            return;
        }

        // Modo mantenimiento — bloquea colegios pero no al superadmin
        try {
            if (ConfigModel::get('sistema_modo_mantenimiento', '0') === '1') {
                http_response_code(503);
                require_once __DIR__ . '/../../views/errors/mantenimiento.php';
                exit;
            }
        } catch (Exception $ignored) {}

        $institucionId = $_SESSION['institucion_id'] ?? null;
        if (!$institucionId) {
            self::bloquear('Tu cuenta no tiene una institución asignada.');
            return;
        }

        // Solo re-consultar si la sesión no tiene plan cargado
        // o si el plan está desactualizado (cambia cada 5 min)
        $ahora  = time();
        $cargado = $_SESSION['plan_cargado_en'] ?? 0;
        if (!empty($_SESSION['plan']) && ($ahora - $cargado) < 300) {
            // Aún vigente en caché de sesión — solo verificar vencimiento
            self::verificarVencimientoEnSesion();
            return;
        }

        // Consultar suscripción activa con todos los campos del plan
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT s.*, s.es_trial, s.trial_dias,
                    p.nombre          AS plan_nombre,
                    p.max_estudiantes, p.max_profesores, p.max_secciones,
                    p.incluye_pagos, p.incluye_reportes,
                    p.incluye_comunicados, p.incluye_api,
                    p.color AS plan_color, p.icono AS plan_icono
             FROM suscripciones s
             INNER JOIN planes p ON s.plan_id = p.id
             WHERE s.institucion_id = :id
               AND s.estado = 'activa'
             ORDER BY s.fecha_vencimiento DESC
             LIMIT 1"
        );
        $stmt->execute([':id' => $institucionId]);
        $susc = $stmt->fetch();

        if (!$susc) {
            self::bloquear('Tu institución no tiene una suscripción activa. Contacta a EduSaaS RD.');
            return;
        }

        // Vencimiento
        if ($susc['fecha_vencimiento'] < date('Y-m-d')) {
            $db->prepare("UPDATE suscripciones SET estado = 'vencida' WHERE id = :id")
               ->execute([':id' => $susc['id']]);
            self::bloquear(
                'Tu suscripción venció el ' .
                date('d/m/Y', strtotime($susc['fecha_vencimiento'])) .
                '. Renueva tu plan para continuar.'
            );
            return;
        }

        // Cargar plan completo en sesión
        $_SESSION['plan'] = [
            // Identificación
            'plan_id'     => $susc['plan_id'],
            'nombre'      => $susc['plan_nombre'],
            'color'       => $susc['plan_color'],
            'icono'       => $susc['plan_icono'],
            // Suscripción
            'susc_id'           => $susc['id'],
            'fecha_vencimiento' => $susc['fecha_vencimiento'],
            'es_trial'          => (bool)($susc['es_trial'] ?? false),
            'trial_dias'        => (int)($susc['trial_dias'] ?? 0),
            // Límites de cantidad (0 = ilimitado)
            'max_estudiantes'   => (int)$susc['max_estudiantes'],
            'max_profesores'    => (int)$susc['max_profesores'],
            'max_secciones'     => (int)$susc['max_secciones'],
            // Módulos habilitados
            'incluye_pagos'        => (bool)$susc['incluye_pagos'],
            'incluye_reportes'     => (bool)$susc['incluye_reportes'],
            'incluye_comunicados'  => (bool)$susc['incluye_comunicados'],
            'incluye_api'          => (bool)$susc['incluye_api'],
        ];
        $_SESSION['plan_cargado_en'] = $ahora;

        // Aviso de vencimiento próximo (sin bloquear)
        $dias = (int)ceil((strtotime($susc['fecha_vencimiento']) - $ahora) / 86400);
        if (!empty($susc['es_trial'])) {
            // Trial: siempre mostrar countdown
            $_SESSION['aviso_trial']      = $dias;
            $_SESSION['trial_dias_total'] = (int)($susc['trial_dias'] ?? 14);
            unset($_SESSION['aviso_vencimiento']);
        } elseif ($dias <= DIAS_AVISO_VENCIMIENTO) {
            $_SESSION['aviso_vencimiento'] = $dias;
            unset($_SESSION['aviso_trial']);
        } else {
            unset($_SESSION['aviso_vencimiento'], $_SESSION['aviso_trial']);
        }
    }

    // ── Privados ─────────────────────────────────────

    private static function verificarVencimientoEnSesion(): void
    {
        $vence = $_SESSION['plan']['fecha_vencimiento'] ?? null;
        if ($vence && $vence < date('Y-m-d')) {
            // Vencido — limpiar caché y bloquear
            unset($_SESSION['plan'], $_SESSION['plan_cargado_en']);
            self::bloquear(
                'Tu suscripción ha vencido. Renueva tu plan para continuar.'
            );
        }

        $dias = $vence
            ? (int)ceil((strtotime($vence) - time()) / 86400)
            : 0;
        if ($dias <= DIAS_AVISO_VENCIMIENTO && $dias >= 0) {
            $_SESSION['aviso_vencimiento'] = $dias;
        }
    }

    private static function bloquear(string $motivo): void
    {
        http_response_code(402); // Payment Required
        require_once __DIR__ . '/../../views/errors/suspendido.php';
        exit;
    }

    /** Plan sin límites — usado por el Super Admin */
    private static function planIlimitado(): array
    {
        return [
            'plan_id'    => 0,
            'nombre'     => 'Super Admin',
            'max_estudiantes'  => 0,
            'max_profesores'   => 0,
            'max_secciones'    => 0,
            'incluye_pagos'        => true,
            'incluye_reportes'     => true,
            'incluye_comunicados'  => true,
            'incluye_api'          => true,
        ];
    }
}