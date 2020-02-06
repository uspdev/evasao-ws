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

http://servidor/evasao/endpoint?format=csv

## Endpoints

* status	"http://servidor/evasao/status"
* listarIngressantes	"http://servidor/evasao/listarIngressantes/{ano}"
* listarRespostasQuestionarioFuvest	"http://servidor/evasao/listarRespostasQuestionarioFuvest/{nusp}, {codqtn}"
* listarHabilitacoes	"http://servidor/evasao/listarHabilitacoes/{codpes}"
* obterHistorico	"http://servidor/evasao/obterHistorico/{codpes}, {codpgm}"