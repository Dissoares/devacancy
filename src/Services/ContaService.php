<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\PerfilProfissional;
use App\Entities\Usuario;
use App\Exceptions\ValidacaoException;
use App\Interfaces\PagamentoRepositoryInterface;
use App\Interfaces\PerfilProfissionalRepositoryInterface;
use App\Interfaces\UsuarioRepositoryInterface;

/** A pessoa excluindo a própria conta (direito previsto na LGPD). */
final class ContaService
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly PerfilProfissionalRepositoryInterface $perfis,
        private readonly PagamentoRepositoryInterface $pagamentos,
        private readonly CurriculoService $curriculos,
        private readonly ImportacaoPerfilService $importacao,
    ) {
    }

    /**
     * Apaga a conta e tudo que depende dela (perfil, candidaturas, assinaturas, pagamentos) e o currículo em PDF.
     * Assinatura recorrente no cartão ou no PayPal continuaria sendo cobrada pelo provedor depois da exclusão,
     * então a exclusão só acontece depois que ela for cancelada.
     * @throws ValidacaoException
     */
    public function excluirPropriaConta(Usuario $usuario, string $confirmacao): void
    {
        if ($usuario->ehAdmin()) {
            throw new ValidacaoException(['geral' => 'Contas de administrador são removidas pelo painel.']);
        }
        if (mb_strtoupper(trim($confirmacao)) !== mb_strtoupper(t('perfilpg.excluir_palavra'))) {
            throw new ValidacaoException(['confirmacao' => t('perfilpg.excluir_erro_palavra')]);
        }
        if ($this->pagamentos->temRecorrenciaAtiva($usuario->id)) {
            throw new ValidacaoException(['geral' => t('perfilpg.excluir_cancele_antes')]);
        }

        $perfil = $usuario->ehProfissional() ? $this->perfis->buscarPorUsuario($usuario->id) : null;
        $this->importacao->descartar();
        $this->usuarios->excluir($usuario->id);
        if ($perfil instanceof PerfilProfissional) {
            $this->curriculos->remover($perfil->curriculoArquivo);
        }
    }
}
