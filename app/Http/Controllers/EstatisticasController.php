<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Infrastructure\Models\Estatistica;

class EstatisticasController extends Controller
{
    public function registrar(Request $request)
{
    // Dados agregados de uso não devem ser registrados integralmente nos logs.

    $validated = $request->validate([
        'session_id'               => 'required|string|max:50',  // aumentei p/ não cortar
        'mapas_selecionados'       => 'required|array',
        'tempo_total'              => 'required|integer|min:0',
        'mapa_recomendado_por_mapa'=> 'nullable|array',
    ]);

    $sessionId   = $validated['session_id'];
    $selecionados= $validated['mapas_selecionados'];  // totais por mapa (ms)
    $tempoTotal  = $validated['tempo_total'];         // total de sessão (s) — manterei como veio
    $recomendados= $validated['mapa_recomendado_por_mapa'] ?? [];

    if (empty($selecionados)) {
        Log::info("⏭️ [estatisticas] Sem mapas — nada a atualizar", ['session' => $sessionId]);
        return response()->json(['message' => 'Sem mapas para registrar', 'updated' => 0, 'created' => 0], 200);
    }

    $created = 0; $updated = 0;

    \DB::beginTransaction();
    try {
        foreach ($selecionados as $mapa => $totalMs) {
            // busca a linha existente (se houver)
            $row = \App\Infrastructure\Models\Estatistica::firstOrNew([
                'ip_usuario' => $sessionId,
                'mapa'       => (string) $mapa,
            ]);

            $isNew = !$row->exists;

            // ⚠️ Atualiza pelos TOTAIS vindos do front (não soma delta aqui)
            $row->tempo        = (int) $totalMs;
            $row->tempo_total  = (int) $tempoTotal;

            // só atualiza 'mapa_recomendado' se vier para este mapa
            if (array_key_exists($mapa, $recomendados)) {
                $row->mapa_recomendado = $recomendados[$mapa];
            }

            $row->save();

            if ($isNew) { $created++; } else { $updated++; }

            Log::info('💾 [estatisticas] Upsert', [
                'id' => $row->id ?? null,
                'session' => $sessionId,
                'mapa' => $mapa,
                'tempo_ms' => (int) $totalMs,
                'tempo_total_s' => (int) $tempoTotal,
                'novo' => $isNew,
            ]);
        }

        \DB::commit();
    } catch (\Throwable $e) {
        \DB::rollBack();
        Log::error('❌ [estatisticas] Erro no upsert', ['ex' => $e]);
        return response()->json(['message' => 'Falha ao registrar estatísticas'], 500);
    }

    return response()->json([
        'message' => 'Estatísticas registradas com sucesso',
        'created' => $created,
        'updated' => $updated,
    ], 200);
}

}
