<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NaoEncontradoException;
use App\Exceptions\ValidacaoException;
use finfo;

final class CurriculoService
{
    private const TAMANHO_MAXIMO = 5 * 1024 * 1024;
    private const TIPO_PERMITIDO = 'application/pdf';

    /**
     * Valida e guarda o PDF fora da pasta pública, com nome aleatório.
     * @return array{arquivo: string, nomeOriginal: string}
     */
    public function armazenar(array $upload): array
    {
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
            throw new ValidacaoException(['curriculo' => t('curriculo.falha_recebimento')]);
        }
        if ($upload['size'] > self::TAMANHO_MAXIMO) {
            throw new ValidacaoException(['curriculo' => t('curriculo.tamanho')]);
        }

        $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
        if ($tipo !== self::TIPO_PERMITIDO) {
            throw new ValidacaoException(['curriculo' => t('curriculo.formato')]);
        }

        $arquivo = bin2hex(random_bytes(16)) . '.pdf';
        if (!move_uploaded_file($upload['tmp_name'], $this->diretorio() . '/' . $arquivo)) {
            throw new ValidacaoException(['curriculo' => t('curriculo.falha_salvar')]);
        }

        $nomeOriginal = mb_substr(preg_replace('/[^\p{L}\p{N}._ -]/u', '', basename((string) $upload['name'])) ?: 'curriculo.pdf', 0, 200);

        return ['arquivo' => $arquivo, 'nomeOriginal' => $nomeOriginal];
    }

    public function caminho(string $arquivo): string
    {
        $caminho = $this->diretorio() . '/' . basename($arquivo);
        if (!is_file($caminho)) {
            throw new NaoEncontradoException();
        }
        return $caminho;
    }

    public function remover(?string $arquivo): void
    {
        if ($arquivo === null) {
            return;
        }
        $caminho = $this->diretorio() . '/' . basename($arquivo);
        if (is_file($caminho)) {
            unlink($caminho);
        }
    }

    private function diretorio(): string
    {
        return RAIZ . '/storage/uploads/curriculos';
    }
}
