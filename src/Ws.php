<?php namespace Uspdev\Evasao;

use Uspdev\Replicado\DB;
use Uspdev\Replicado\Uteis;

class Ws
{
    public static function listarColegiados()
    {
        return ['colegiados' => getenv('CODCLG')];
    }

    /**
     * listarIngressantes - lista ingressantes nos colegiados definidos no ambiente
     *
     * Sistema evasão
     *
     * @param  int $ano - Ano a ser listado (YYYY)
     *
     * @return void
     */
    public function listarIngressantes($ano)
    {
        $codclg = getenv('CODCLG');

        $sql = "SELECT *
        FROM PROGRAMAGR AS p, HABILPROGGR AS h, CURSOGR AS c
        WHERE datepart(year,p.dtaing) = $ano
            AND p.codpes = h.codpes
            AND h.codcur = c.codcur
            AND p.codpgm = h.codpgm
            AND c.codclg IN ($codclg)
            AND p.tiping NOT IN ('Especial')
        ORDER BY h.codcur, h.codhab";

        $list = DB::fetchAll($sql);
        $list = Uteis::trim_recursivo($list);
        //print_r($list);exit;

        $filtro['dtaini'] = $ano;
        foreach ($list as $r) {
            $arr = [];
            $arr['ano_ingresso'] = $ano;
            $arr['identificacao'] = $r['codpes'];
            $arr['tipo_ingresso'] = $r['tiping'];
            $arr['classif_ingresso'] = $r['clsing'];
            $arr['cod_curso'] = $r['codcur'];
            $arr['nome_curso'] = $r['nomcur'];
            $arr['cod_habilitacao'] = $r['codhab'];
            $arr['cod_programa'] = $r['codpgm'];
            $arr['data_ingresso'] = date('d/m/Y', strtotime($r['dtaini']));
            $arr['data_situacao'] = strtotime($r['dtafim']) ? date('d/m/Y', strtotime($r['dtafim'])) : '';
            $arr['status_programa'] = $r['stapgm'];
            $arr['descr_status_programa'] = SELF::statusPrograma($r['stapgm']);
            $arr['tipo_encerramento'] = $r['tipenchab'];
            $ret[] = $arr;
        }

        return $ret;
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

    public function obterHistorico($codpes, $codpgm = 0)
    {
        $codpgm = ($codpgm) ? $codpgm : SELF::listarProgramasHistorico($codpes);

        $sql = "SELECT SUBSTRING(h.codtur, 1, 5) AS semestre, *
        FROM histescolargr AS h
        WHERE codpes= :codpes
            AND codpgm = :codpgm
        ORDER BY codpgm, semestre, dtacrihst";

        $param['codpes'] = $codpes;
        $param['codpgm'] = $codpgm;

        $list = DB::fetchAll($sql, $param);
        $list = Uteis::trim_recursivo($list);
        $ret = [];
        foreach ($list as $row) {
            unset($row['timestamp']);
            $ret[] = $row;
        }
        return $ret;
    }

    private static function listarProgramasHistorico($codpes)
    {
        // aqui vai pegar somente o ultimo programa.
        // todo: multiplos programas
        $sql = "SELECT MAX(codpgm) AS codpgm
        FROM HISTESCOLARGR
        WHERE codpes = :codpes";
        $param['codpes'] = $codpes;
        return DB::fetch($sql, $param)['codpgm'];
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
