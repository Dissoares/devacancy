<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Visao;
use App\DTOs\FiltroUsuariosDTO;
use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\Senioridade;
use App\Enums\TamanhoEmpresa;
use App\Enums\TipoUsuario;
use App\Services\AutenticacaoService;
use App\Services\UsuarioService;

final class AdminController
{
    public function __construct(
        private readonly UsuarioService $usuarios,
        private readonly AutenticacaoService $autenticacao,
    ) {
    }

    public function painel(Requisicao $requisicao): Resposta
    {
        $filtro = FiltroUsuariosDTO::deRequisicao($requisicao);

        return Resposta::html(Visao::renderizar('admin/painel', [
            'titulo' => 'Cadastros — Devacancy',
            'usuario' => $this->autenticacao->usuarioAtual(),
            'estatisticas' => $this->usuarios->estatisticas(),
            'filtro' => $filtro,
            'resultado' => $this->usuarios->listar($filtro),
        ], 'app'));
    }

    /** Tabela atualizada pelo HTMX ao buscar, filtrar ou paginar. */
    public function listar(Requisicao $requisicao): Resposta
    {
        $filtro = FiltroUsuariosDTO::deRequisicao($requisicao);

        return Resposta::html(Visao::parcial('admin/partials/tabela', [
            'filtro' => $filtro,
            'resultado' => $this->usuarios->listar($filtro),
        ]))->comCabecalho('HX-Push-Url', '/admin' . (($consulta = $filtro->paraConsulta()) ? '?' . $consulta : ''));
    }

    public function detalhe(Requisicao $requisicao): Resposta
    {
        $usuario = $this->usuarios->buscarPorId((int) $requisicao->parametro('id'));

        return Resposta::html(Visao::parcial('admin/partials/detalhe', [
            'usuario' => $usuario,
            'perfil' => $this->usuarios->perfilDe($usuario),
        ]));
    }

    public function curriculo(Requisicao $requisicao): Resposta
    {
        $usuario = $this->usuarios->buscarPorId((int) $requisicao->parametro('id'));
        $arquivo = $this->usuarios->caminhoCurriculo($usuario);

        return Resposta::download($arquivo['caminho'], $arquivo['nome'], 'application/pdf');
    }

    public function exportar(Requisicao $requisicao): Resposta
    {
        $linhas = $this->usuarios->listarTodos(FiltroUsuariosDTO::deRequisicao($requisicao));

        $saida = fopen('php://temp', 'r+');
        fputcsv($saida, [
            'ID', 'Tipo', 'Nome', 'E-mail', 'WhatsApp', 'Cadastro em', 'Perfil completo',
            'Área', 'Senioridade', 'Cargo desejado', 'Anos de experiência', 'Stack', 'Modelo de trabalho',
            'Cidade', 'Pretensão (R$)', 'LinkedIn', 'GitHub', 'Portfólio',
            'Empresa', 'Site', 'Tamanho', 'Cargo do contato', 'Stacks que contrata', 'Vagas abertas',
        ], ';');

        foreach ($linhas as $linha) {
            fputcsv($saida, array_map($this->celulaSegura(...), [
                $linha['id'],
                TipoUsuario::rotuloDe($linha['tipo']),
                $linha['nome'],
                $linha['email'],
                $linha['telefone'],
                data_br($linha['criado_em'], true),
                $linha['cadastro_completo'] ? 'Sim' : 'Não',
                $linha['area'] ? AreaAtuacao::rotuloDe($linha['area']) : '',
                $linha['senioridade'] ? Senioridade::rotuloDe($linha['senioridade']) : '',
                $linha['cargo_desejado'],
                $linha['anos_experiencia'],
                implode(', ', json_decode($linha['stack'] ?? '[]', true) ?: []),
                $linha['modelo_trabalho'] ? ModeloTrabalho::rotuloDe($linha['modelo_trabalho']) : '',
                $linha['cidade'],
                $linha['pretensao_salarial'],
                $linha['linkedin_url'],
                $linha['github_url'],
                $linha['portfolio_url'],
                $linha['nome_empresa'],
                $linha['site'],
                $linha['tamanho'] ? TamanhoEmpresa::rotuloDe($linha['tamanho']) : '',
                $linha['cargo_contato'],
                implode(', ', json_decode($linha['stacks_contratacao'] ?? '[]', true) ?: []),
                $linha['vagas_abertas'],
            ]), ';');
        }

        rewind($saida);
        $conteudo = stream_get_contents($saida);
        fclose($saida);

        return Resposta::csv($conteudo, 'cadastros-devacancy-' . date('Y-m-d') . '.csv');
    }

    /** Impede que um texto como "=HYPERLINK(...)" vire fórmula ao abrir no Excel. */
    private function celulaSegura(mixed $valor): string
    {
        $texto = (string) ($valor ?? '');
        return preg_match('/^[=+\-@\t\r]/', $texto) ? "'" . $texto : $texto;
    }
}
