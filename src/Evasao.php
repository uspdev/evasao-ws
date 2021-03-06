<?php namespace Uspdev\Evasao;

use Uspdev\Replicado\DB;
use Uspdev\Replicado\Uteis;

class Evasao
{
    /**
     * listarIngressantes - lista ingressantes nos colegiados definidos no ambiente
     *
     * @param  Int $ano - Ano a ser listado (formato YYYY)
     *
     * @return Array
     */
    public function listarIngressantes($ano)
    {
        if (!$ano) {
            return [];
        }

        $codclg = getenv('CODCLG');

        $sql = "SELECT *
        FROM PROGRAMAGR AS p, HABILPROGGR AS h, CURSOGR AS c
        WHERE datepart(year,p.dtaing) = :ano
            AND p.codpes = h.codpes
            AND h.codcur = c.codcur
            AND p.codpgm = h.codpgm
            AND c.codclg IN ( $codclg )
            AND p.tiping NOT IN ('Especial')
        ORDER BY h.codcur, h.codhab";
        $params['ano'] = $ano;
        //$params['codclg'] = $codclg; // nao podemos passar uma lista como binding
        //https://stackoverflow.com/questions/920353/can-i-bind-an-array-to-an-in-condition

        $list = DB::fetchAll($sql, $params);

        $ret = [];
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

    /**
     * listarRespostasQuestionarioFuvest - Método que retorna as respostas de determinado aluno no questionário Fuvest
     *
     * @param $nusp número USP do aluno
     * @param $codqtn codigo do questionário das perguntas
     *
     * @return Array com as perguntas e respostas
     */
    public function listarRespostasQuestionarioFuvest($nusp, $codqtn = 0)
    {
        //$codqtn = 309;
        // aqui é para saber quantos questionários foram respondidos
        if (empty($codqtn)) {
            $questionarios = SELF::listarQuestionariosRespondidos($nusp);
        } else {
            $questionarios = (obj) [['codqtn' => $codqtn]];
        }

        $ret = [];
        foreach ($questionarios as $q) {
            $arr = [];
            $respostas = SELF::listarRespostas($nusp, $q['codqtn']);
            $ret1 = [];
            foreach ($respostas as $r) {
                $arr['identificacao'] = $nusp;
                $arr['cod_questionario'] = $r['codqtn'];
                $arr['ano_ingresso'] = substr($r['dtaini'], 0, 4);
                $arr['cod_curso'] = $r['codcur'];
                $arr['cod_habilitacao'] = $r['codhab'];
                $arr['cod_programa'] = $r['codpgm'];
                $arr['cod_questionario'] = $r['codqtn'];
                $arr['cod_questao'] = $r['codqst'];
                $arr['questao'] = $r['dscqst'];
                $arr['cod_resposta'] = $r['numatnqst'];
                $arr['resposta'] = $r['dscatn'];
                $arr['pontos_soc_eco'] = $r['qtdptosoceco'] ? $r['qtdptosoceco'] : 0;
                $arr['respposta_alt'] = $r['rpaatn'];
                $arr['status_texto_compl'] = $r['statxtcpl'];
                $arr['texto_compl_resp'] = $r['txtcplrpa'];
                $arr['dtarpa'] = $r['dtarpa'];
                $arr['numseqsrv'] = $r['numseqsrv'];

                $ret1[] = $arr;
            }
            $ret[$q['codqtn']] = $ret1;
            unset($ret1);
        }
        
        return $ret;
    }

    /**
     * obterHistorico - Retorna a lista de disciplinas cursadas por determinado aluno
     *
     * @param  Int $codpes
     * @param  Int $codpgm
     *
     * @return Array
     */
    public function obterHistorico($codpes, $codpgm = 0)
    {
        $codpgm = ($codpgm) ? $codpgm : SELF::listarProgramasHistorico($codpes);

        $sql = "SELECT SUBSTRING(h.codtur, 1, 5) AS semestre, *
        FROM HISTESCOLARGR AS h
        WHERE codpes= :codpes
            AND codpgm = :codpgm
        ORDER BY codpgm, semestre, dtacrihst";

        $param['codpes'] = $codpes;
        $param['codpgm'] = $codpgm;

        $list = DB::fetchAll($sql, $param);

        $ret = [];
        foreach ($list as $row) {
            unset($row['timestamp']);
            $ret[] = $row;
        }
        return $ret;
    }

    /**
     * listarHabilitacoes - Lista as habilitações associadas a determinado aluno
     *
     * @param  Int $codpes - Número USp do aluno
     *
     * @return Array
     */
    public function listarHabilitacoes($codpes)
    {
        $sql = "SELECT c.nomcur, hg.nomhab, cl.nomabvclg ,h.*
        FROM HABILPROGGR AS h
        INNER JOIN CURSOGR AS c ON h.codcur = c.codcur
        INNER JOIN HABILITACAOGR AS hg ON h.codcur = hg.codcur AND h.codhab = hg.codhab
        INNER JOIN COLEGIADO as cl ON c.codclg = cl.codclg and cl.sglclg = 'CG'
        --INNER JOIN programagr AS p ON h.codpes = p.codpes AND h.codpgm = p.codpgm
        WHERE h.codpes = :codpes
        ORDER BY h.dtaini";

        $param['codpes'] = $codpes;

        $list = DB::fetchAll($sql, $param);

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
        $sql = "SELECT * FROM PROGRAMAGR
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

    // Conforme documentação do replicado
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

    // todas as informacoes da resposta mais a pergunta textual e a resposta textual
    // pode ter mais de um questionario respondido então vem ordenado por dtaini para podeseparar
    private static function listarRespostas($codpes, $codqtn = 0)
    {
        //$codqtn_sql = $codqtn ? " AND r.codqtn = " . $codqtn : '';
        $sql = "SELECT *
        FROM RESPOSTASQUESTAO AS r
		INNER JOIN ALTERNATIVAQUESTAO AS a
            ON r.codqtn = a.codqtn AND r.numatnqst = a.numatnqst AND r.codqst = a.codqst
        INNER JOIN QUESTOESPESQUISA as q
            ON r.codqtn = q.codqtn AND r.codqst = q.codqst
        INNER JOIN PROGRAMAGR AS p
            ON r.codpes = p.codpes
        INNER JOIN HABILPROGGR AS h
      		ON r.codpes = h.codpes AND DATEPART(YEAR, p.dtaing) = DATEPART(YEAR, h.dtaini)
        WHERE r.codpes =  :codpes
            AND r.codqtn = CONVERT(int,:codqtn)
        -- AND tiping = 'Vestibular' -- pode ser transferencia então não vamos filtrar isso
        ORDER BY h.dtaini, a.codqtn, a.codqst";

        $params['codpes'] = $codpes;
        $params['codqtn'] = $codqtn;
        return DB::fetchAll($sql, $params);
    }

    // vai morrer provavelmente
    // pois retorna o codqtn mas ele não serve para diferenciar pois um mesmo codqtn serve para vários anos

    private static function listarQuestionariosRespondidos($codpes)
    {
        $sql = 'SELECT DISTINCT codqtn
        FROM RESPOSTASQUESTAO
        WHERE codpes = :codpes';
        $params['codpes'] = $codpes;


        $list = DB::fetchAll($sql, $params);
        return $list;
    }
}
