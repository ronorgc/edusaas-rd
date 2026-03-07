<?php
// =====================================================
// EduSaaS RD - MetricasService
//
// Calcula todas las métricas de negocio SaaS:
// MRR, ARR, Churn, ARPU, Growth, proyecciones, etc.
// =====================================================

class MetricasService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ──────────────────────────────────────────────────
    // MÉTODO PRINCIPAL
    // Devuelve todas las métricas en un solo array.
    // ──────────────────────────────────────────────────

    public function calcularTodo(): array
    {
        return [
            // Ingresos recurrentes
            'mrr'                 => $this->calcularMRR(),
            'arr'                 => $this->calcularARR(),
            'arpu'                => $this->calcularARPU(),

            // Clientes
            'clientes_activos'    => $this->clientesActivos(),
            'clientes_nuevos_mes' => $this->clientesNuevosMes(),
            'clientes_perdidos'   => $this->clientesPerdidosMes(),
            'churn_rate'          => $this->calcularChurn(),

            // Ingresos realizados
            'ingresos_mes'        => $this->ingresosMes(),
            'ingresos_mes_anterior' => $this->ingresosMesAnterior(),
            'ingresos_anio'       => $this->ingresosAnio(),
            'crecimiento_mom'     => $this->crecimientoMoM(),   // Month-over-Month %

            // Proyecciones
            'proyeccion_anio'     => $this->proyeccionAnual(),
            'ingresos_riesgo'     => $this->ingresosEnRiesgo(),  // Vencen en 30 días

            // Distribución por plan
            'por_plan'            => $this->distribucionPorPlan(),

            // Series temporales para gráficos
            'ingresos_12_meses'   => $this->ingresos12Meses(),
            'clientes_12_meses'   => $this->clientes12Meses(),

            // Actividad
            'total_estudiantes'   => $this->totalEstudiantes(),
            'pagos_pendientes'    => $this->pagosPendientesEstesMes(),
        ];
    }

    // ──────────────────────────────────────────────────
    // MRR — Monthly Recurring Revenue
    // Suma el equivalente mensual de todas las
    // suscripciones activas. Las anuales se dividen /12.
    // ──────────────────────────────────────────────────

    public function calcularMRR(): float
    {
        $stmt = $this->db->query(
            "SELECT tipo_facturacion, SUM(monto) AS total
             FROM suscripciones
             WHERE estado = 'activa'
             GROUP BY tipo_facturacion"
        );
        $rows = $stmt->fetchAll();

        $mrr = 0;
        foreach ($rows as $row) {
            $mrr += $row['tipo_facturacion'] === 'anual'
                ? (float)$row['total'] / 12
                : (float)$row['total'];
        }
        return round($mrr, 2);
    }

    // ARR = MRR × 12
    public function calcularARR(): float
    {
        return round($this->calcularMRR() * 12, 2);
    }

    // ARPU — Average Revenue Per User
    public function calcularARPU(): float
    {
        $activos = $this->clientesActivos();
        if ($activos === 0) return 0;
        return round($this->calcularMRR() / $activos, 2);
    }

    // ──────────────────────────────────────────────────
    // CLIENTES
    // ──────────────────────────────────────────────────

    public function clientesActivos(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM suscripciones WHERE estado = 'activa'"
        )->fetchColumn();
    }

    public function clientesNuevosMes(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM instituciones
             WHERE MONTH(created_at) = MONTH(CURDATE())
               AND YEAR(created_at)  = YEAR(CURDATE())"
        )->fetchColumn();
    }

    public function clientesPerdidosMes(): int
    {
        // Suscripciones que vencieron o fueron canceladas este mes
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM suscripciones
             WHERE estado IN ('vencida','cancelada')
               AND MONTH(updated_at) = MONTH(CURDATE())
               AND YEAR(updated_at)  = YEAR(CURDATE())"
        )->fetchColumn();
    }

    // ──────────────────────────────────────────────────
    // CHURN RATE
    // (Clientes perdidos este mes / Clientes inicio de mes) × 100
    // ──────────────────────────────────────────────────

    public function calcularChurn(): float
    {
        $perdidos = $this->clientesPerdidosMes();
        // Clientes al inicio del mes = activos actuales + los perdidos este mes
        $baseClientes = $this->clientesActivos() + $perdidos;
        if ($baseClientes === 0) return 0;
        return round(($perdidos / $baseClientes) * 100, 1);
    }

    // ──────────────────────────────────────────────────
    // INGRESOS REALIZADOS (pagos confirmados)
    // ──────────────────────────────────────────────────

    public function ingresosMes(): float
    {
        return (float) $this->db->query(
            "SELECT COALESCE(SUM(monto), 0) FROM pagos_saas
             WHERE estado = 'confirmado'
               AND MONTH(fecha_pago) = MONTH(CURDATE())
               AND YEAR(fecha_pago)  = YEAR(CURDATE())"
        )->fetchColumn();
    }

    public function ingresosMesAnterior(): float
    {
        return (float) $this->db->query(
            "SELECT COALESCE(SUM(monto), 0) FROM pagos_saas
             WHERE estado = 'confirmado'
               AND fecha_pago >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01')
               AND fecha_pago <  DATE_FORMAT(CURDATE(), '%Y-%m-01')"
        )->fetchColumn();
    }

    public function ingresosAnio(): float
    {
        return (float) $this->db->query(
            "SELECT COALESCE(SUM(monto), 0) FROM pagos_saas
             WHERE estado = 'confirmado'
               AND YEAR(fecha_pago) = YEAR(CURDATE())"
        )->fetchColumn();
    }

    // ──────────────────────────────────────────────────
    // MoM GROWTH — Crecimiento mes sobre mes en %
    // ──────────────────────────────────────────────────

    public function crecimientoMoM(): float
    {
        $actual   = $this->ingresosMes();
        $anterior = $this->ingresosMesAnterior();
        if ($anterior == 0) return $actual > 0 ? 100 : 0;
        return round((($actual - $anterior) / $anterior) * 100, 1);
    }

    // ──────────────────────────────────────────────────
    // PROYECCIÓN ANUAL
    // Lo ya cobrado en el año + MRR × meses restantes
    // ──────────────────────────────────────────────────

    public function proyeccionAnual(): float
    {
        $mesesRestantes = 12 - (int)date('n'); // meses que faltan para fin de año
        $cobrado        = $this->ingresosAnio();
        return round($cobrado + ($this->calcularMRR() * $mesesRestantes), 2);
    }

    // ──────────────────────────────────────────────────
    // INGRESOS EN RIESGO
    // MRR de colegios que vencen en los próximos 30 días
    // ──────────────────────────────────────────────────

    public function ingresosEnRiesgo(): float
    {
        return (float) $this->db->query(
            "SELECT COALESCE(SUM(
                CASE tipo_facturacion
                    WHEN 'anual'   THEN monto / 12
                    ELSE monto
                END
             ), 0)
             FROM suscripciones
             WHERE estado = 'activa'
               AND fecha_vencimiento BETWEEN CURDATE()
                   AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
        )->fetchColumn();
    }

    // ──────────────────────────────────────────────────
    // DISTRIBUCIÓN POR PLAN
    // ──────────────────────────────────────────────────

    public function distribucionPorPlan(): array
    {
        $stmt = $this->db->query(
            "SELECT p.nombre, p.color,
                    COUNT(s.id)     AS cantidad,
                    SUM(s.monto)    AS ingresos_brutos
             FROM suscripciones s
             INNER JOIN planes p ON s.plan_id = p.id
             WHERE s.estado = 'activa'
             GROUP BY p.id, p.nombre, p.color
             ORDER BY p.orden ASC"
        );
        return $stmt->fetchAll();
    }

    // ──────────────────────────────────────────────────
    // SERIES PARA GRÁFICOS (12 meses hacia atrás)
    // ──────────────────────────────────────────────────

    public function ingresos12Meses(): array
    {
        $stmt = $this->db->query(
            "SELECT DATE_FORMAT(fecha_pago, '%Y-%m') AS mes,
                    COALESCE(SUM(monto), 0)          AS total
             FROM pagos_saas
             WHERE estado = 'confirmado'
               AND fecha_pago >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
             GROUP BY DATE_FORMAT(fecha_pago, '%Y-%m')
             ORDER BY mes ASC"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Llenar los 12 meses aunque no haya pagos
        $resultado = [];
        for ($i = 11; $i >= 0; $i--) {
            $clave = date('Y-m', strtotime("-{$i} months"));
            $resultado[$clave] = (float)($rows[$clave] ?? 0);
        }
        return $resultado;
    }

    public function clientes12Meses(): array
    {
        // Clientes activos al final de cada mes (aproximado con fecha de creación)
        $stmt = $this->db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mes,
                    COUNT(*) AS nuevos
             FROM instituciones
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY mes ASC"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $resultado = [];
        for ($i = 11; $i >= 0; $i--) {
            $clave = date('Y-m', strtotime("-{$i} months"));
            $resultado[$clave] = (int)($rows[$clave] ?? 0);
        }
        return $resultado;
    }

    // ──────────────────────────────────────────────────
    // EXTRAS
    // ──────────────────────────────────────────────────

    public function totalEstudiantes(): int
    {
        try {
            return (int) $this->db->query(
                "SELECT COUNT(*) FROM estudiantes WHERE activo = 1"
            )->fetchColumn();
        } catch (Exception $e) {
            return 0; // tabla puede no existir aún
        }
    }

    public function pagosPendientesEstesMes(): int
    {
        // Instituciones activas que NO han pagado este mes
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM suscripciones s
             WHERE s.estado = 'activa'
               AND s.institucion_id NOT IN (
                   SELECT DISTINCT institucion_id FROM pagos_saas
                   WHERE estado = 'confirmado'
                     AND MONTH(fecha_pago) = MONTH(CURDATE())
                     AND YEAR(fecha_pago)  = YEAR(CURDATE())
               )"
        )->fetchColumn();
    }
}
