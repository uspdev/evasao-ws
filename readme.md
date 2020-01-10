# Evasão Webservice

Provê dados para análise de evasão de cursos de graduação

## Requisitos

* uspdev/replicado
* uspdev/cache

## Configuração

Copie o .env.example para .env e faça as alterações necessárias

    cp .env.exampl .env

Instale as dependências do composer

    composer install

## Utilização

Os endpoits retornam json por padrão mas é possivel retornar outro formato
* csv = para uso no excel
* json = json formatado

http://servidor/endpoint?format=csv

## Endpoints

* status	"http://143.107.233.112/git/uspdev/evasao-ws/public/status"
* listarIngressantes	"http://143.107.233.112/git/uspdev/evasao-ws/public/listarIngressantes/{ano}"
* listarRespostasQuestionarioFuvest	"http://143.107.233.112/git/uspdev/evasao-ws/public/listarRespostasQuestionarioFuvest/{nusp}, {codqtn}"
* listarHabilitacoes	"http://143.107.233.112/git/uspdev/evasao-ws/public/listarHabilitacoes/{codpes}"
* obterHistorico	"http://143.107.233.112/git/uspdev/evasao-ws/public/obterHistorico/{codpes}, {codpgm}"