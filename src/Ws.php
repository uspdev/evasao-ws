<?php namespace Uspdev\Evasao;

use Uspdev\Replicado\DB;
use Uspdev\Replicado\Uteis;

class Ws
{
    public static function listarColegiados()
    {
        return getenv('CODCLG');
    }

    public function listarHabilitacoes($codpes)
    {
        $sql = "SELECT c.nomcur, hg.nomhab, cl.nomabvclg ,h.*
        FROM habilproggr AS h
        INNER JOIN cursogr AS c ON h.codcur = c.codcur
        INNER JOIN habilitacaogr AS hg ON h.codcur = hg.codcur AND h.codhab = hg.codhab
        INNER JOIN colegiado as cl ON c.codclg = cl.codclg and cl.sglclg = 'CG'
        --INNER JOIN programagr AS p ON h.codpes = p.codpes AND h.codpgm = p.codpgm
        WHERE h.codpes = :codpes
        ORDER BY h.dtaini";

        $param['codpes'] = $codpes;

        $list = DB::fetchAll($sql, $param);
        $list = Uteis::trim_recursivo($list);

        $ret = [];
        foreach ($list as $row) {
            unset($row['timestamp']);
            if ($programagr = SELF::obterPrograma($row['codpes'], $row['dtaini'])) {
                $row['tiping'] = $programagr['tiping'];
                $row['clsing'] = $programagr['clsing'];
                $row['stapgm'] = $programagr['stapgm'];
                $row['status_programa'] = SELF::statusPrograma($programagr['stapgm']);
            }
            $ret[] = $row;
        }
        return $ret;
    }

    // vamos obter o progrma correspondente verificando a data e o codpes.
    // Usar o codpgm não dá certo para o codpes 9393256 p.ex.
    private static function obterPrograma($codpes, $dtaini)
    {

        // vamos usar um intervalo para dtaing pois pode divergir em relação ao dtaini
        // possivelemnte por conta de data de cadastro
        $sql = "SELECT * FROM programagr
        WHERE codpes = :codpes
            AND dtaing >= DATEADD(DAY, -2, :dtaini)
            AND dtaing <= DATEADD(DAY, 4, :dtaini)
        ";
        $param['codpes'] = $codpes;
        $param['dtaini'] = $dtaini;

        $list = DB::fetchAll($sql, $param);
        if ($list) {
            $list = $list[0];
        }
        //print_r($list);exit;
        $list = Uteis::trim_recursivo($list);
        return $list;
    }

    private static function statusPrograma($stapgm)
    {
        $stapgm_desc['A'] = "Ativo - programa em andamento";
        $stapgm_desc['E'] = "Encerrado - programa encerrado";
        $stapgm_desc['T'] = "Trancado - trancamento total da matrícula";
        $stapgm_desc['R'] = "Reativado - programa reativado";
        $stapgm_desc['S'] = "Suspenso - programa suspenso por punição";
        $stapgm_desc['P'] = "Pendente - programa inativo";
        $stapgm_desc['H'] = "Histórico - indica qualquer situação de Histórico que não se refira a alteração de estado do programa";
        $stapgm_desc['EH'] = "Encerramento de Habilitação";
        return $stapgm_desc[$stapgm];
    }
}
