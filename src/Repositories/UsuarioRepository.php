<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\FiltroUsuariosDTO;
use App\Entities\Usuario;
use App\Enums\ProvedorSocial;
use App\Enums\TipoUsuario;
use App\Interfaces\UsuarioRepositoryInterface;
use PDO;

final class UsuarioRepository implements UsuarioRepositoryInterface
{
    private const COLUNAS_LISTAGEM = '
        u.id, u.tipo, u.nome, u.email, u.telefone, u.avatar_url, u.cadastro_completo, u.criado_em, u.ultimo_acesso_em,
        pp.area, pp.senioridade, pp.cargo_desejado, pp.anos_experiencia, pp.stack, pp.modelo_trabalho, pp.cidade,
        pp.pretensao_salarial, pp.linkedin_url, pp.github_url, pp.portfolio_url, pp.curriculo_arquivo,
        pe.nome_empresa, pe.site, pe.tamanho, pe.cargo_contato, pe.stacks_contratacao, pe.vagas_abertas,
        a.plano AS plano_assinatura, a.expira_em AS assinatura_expira_em';

    private const JUNCAO_ASSINATURA = 'LEFT JOIN assinaturas a ON a.usuario_id = u.id AND a.expira_em > NOW()';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function buscarPorId(int $id): ?Usuario
    {
        return $this->buscarUm('SELECT * FROM usuarios WHERE id = ?', [$id]);
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        return $this->buscarUm('SELECT * FROM usuarios WHERE email = ?', [mb_strtolower($email)]);
    }

    public function buscarPorContaSocial(ProvedorSocial $provedor, string $provedorId): ?Usuario
    {
        return $this->buscarUm(
            'SELECT u.* FROM usuarios u
               JOIN contas_sociais cs ON cs.usuario_id = u.id
              WHERE cs.provedor = ? AND cs.provedor_id = ?',
            [$provedor->value, $provedorId],
        );
    }

    public function emailExiste(string $email): bool
    {
        $consulta = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE email = ? LIMIT 1');
        $consulta->execute([mb_strtolower($email)]);
        return (bool) $consulta->fetchColumn();
    }

    public function criar(TipoUsuario $tipo, string $nome, string $email, ?string $senhaHash, ?string $avatarUrl = null, string $idioma = 'pt', ?string $pais = null, ?string $codigoIndicacaoUsado = null): Usuario
    {
        $indicadoPor = $codigoIndicacaoUsado !== null ? $this->buscarIdPorCodigoIndicacao($codigoIndicacaoUsado) : null;

        $consulta = $this->pdo->prepare(
            'INSERT INTO usuarios (tipo, nome, email, senha_hash, avatar_url, idioma, pais, codigo_indicacao, indicado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $consulta->execute([$tipo->value, $nome, mb_strtolower($email), $senhaHash, $avatarUrl, $idioma, $pais, $this->gerarCodigoIndicacaoUnico(), $indicadoPor]);

        return $this->buscarPorId((int) $this->pdo->lastInsertId());
    }

    /** Código curto e único pra essa pessoa indicar amigos (ex: ?ref=A1B2C3D4). */
    private function gerarCodigoIndicacaoUnico(): string
    {
        do {
            $codigo = strtoupper(bin2hex(random_bytes(4)));
        } while ($this->buscarIdPorCodigoIndicacao($codigo) !== null);

        return $codigo;
    }

    public function garantirCodigoIndicacao(int $usuarioId): string
    {
        $consulta = $this->pdo->prepare('SELECT codigo_indicacao FROM usuarios WHERE id = ?');
        $consulta->execute([$usuarioId]);
        $codigo = $consulta->fetchColumn();
        if ($codigo) {
            return $codigo;
        }

        $novo = $this->gerarCodigoIndicacaoUnico();
        $atualizado = $this->pdo->prepare('UPDATE usuarios SET codigo_indicacao = ? WHERE id = ? AND codigo_indicacao IS NULL');
        $atualizado->execute([$novo, $usuarioId]);
        if ($atualizado->rowCount() === 1) {
            return $novo;
        }

        // Duas requisições chegaram juntas e a outra já gravou um código: usa o que ficou salvo.
        $consulta->execute([$usuarioId]);
        return (string) $consulta->fetchColumn();
    }

    private function buscarIdPorCodigoIndicacao(string $codigo): ?int
    {
        $consulta = $this->pdo->prepare('SELECT id FROM usuarios WHERE codigo_indicacao = ?');
        $consulta->execute([mb_strtoupper(trim($codigo))]);
        $id = $consulta->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    public function vincularContaSocial(int $usuarioId, ProvedorSocial $provedor, string $provedorId): void
    {
        $this->pdo->prepare(
            'INSERT IGNORE INTO contas_sociais (usuario_id, provedor, provedor_id) VALUES (?, ?, ?)'
        )->execute([$usuarioId, $provedor->value, $provedorId]);
    }

    public function atualizarAvatarSeVazio(int $usuarioId, ?string $avatarUrl): void
    {
        if ($avatarUrl === null) {
            return;
        }
        $this->pdo->prepare('UPDATE usuarios SET avatar_url = ? WHERE id = ? AND avatar_url IS NULL')
            ->execute([$avatarUrl, $usuarioId]);
    }

    public function atualizarTelefone(int $usuarioId, ?string $telefone): void
    {
        if ($telefone === null) {
            return;
        }
        $this->pdo->prepare('UPDATE usuarios SET telefone = ? WHERE id = ?')->execute([$telefone, $usuarioId]);
    }

    public function marcarCadastroCompleto(int $usuarioId): void
    {
        $this->pdo->prepare('UPDATE usuarios SET cadastro_completo = 1 WHERE id = ?')->execute([$usuarioId]);
    }

    public function atualizarSenha(int $usuarioId, string $senhaHash): void
    {
        $this->pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?')->execute([$senhaHash, $usuarioId]);
    }

    public function promoverAAdmin(int $usuarioId): void
    {
        $this->pdo->prepare("UPDATE usuarios SET tipo = 'admin', cadastro_completo = 1 WHERE id = ?")->execute([$usuarioId]);
    }

    public function excluir(int $usuarioId): void
    {
        $this->pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$usuarioId]);
    }

    public function contarAdmins(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'admin'")->fetchColumn();
    }

    public function listarAdmins(): array
    {
        $linhas = $this->pdo->query("SELECT * FROM usuarios WHERE tipo = 'admin' ORDER BY criado_em")->fetchAll();
        return array_map(Usuario::deLinha(...), $linhas);
    }

    public function registrarAcesso(int $usuarioId): void
    {
        $this->pdo->prepare('UPDATE usuarios SET ultimo_acesso_em = NOW() WHERE id = ?')->execute([$usuarioId]);
    }

    public function listar(FiltroUsuariosDTO $filtro): array
    {
        [$onde, $parametros] = $this->montarFiltro($filtro);

        $total = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios u
            LEFT JOIN perfis_profissionais pp ON pp.usuario_id = u.id
            LEFT JOIN perfis_empresas pe ON pe.usuario_id = u.id
            {$onde}");
        $total->execute($parametros);

        $consulta = $this->pdo->prepare(sprintf(
            'SELECT %s FROM usuarios u
               LEFT JOIN perfis_profissionais pp ON pp.usuario_id = u.id
               LEFT JOIN perfis_empresas pe ON pe.usuario_id = u.id
               %s
               %s ORDER BY u.criado_em DESC, u.id DESC LIMIT %d OFFSET %d',
            self::COLUNAS_LISTAGEM,
            self::JUNCAO_ASSINATURA,
            $onde,
            FiltroUsuariosDTO::POR_PAGINA,
            $filtro->deslocamento(),
        ));
        $consulta->execute($parametros);

        return ['itens' => $consulta->fetchAll(), 'total' => (int) $total->fetchColumn()];
    }

    public function listarTodos(FiltroUsuariosDTO $filtro): array
    {
        [$onde, $parametros] = $this->montarFiltro($filtro);

        $consulta = $this->pdo->prepare(sprintf(
            'SELECT %s FROM usuarios u
               LEFT JOIN perfis_profissionais pp ON pp.usuario_id = u.id
               LEFT JOIN perfis_empresas pe ON pe.usuario_id = u.id
               %s
               %s ORDER BY u.criado_em DESC',
            self::COLUNAS_LISTAGEM,
            self::JUNCAO_ASSINATURA,
            $onde,
        ));
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    public function estatisticas(): array
    {
        $linha = $this->pdo->query(
            "SELECT
                SUM(tipo = 'profissional') AS profissionais,
                SUM(tipo = 'empresa') AS empresas,
                SUM(tipo <> 'admin' AND criado_em >= NOW() - INTERVAL 7 DAY) AS novos_semana,
                SUM(tipo <> 'admin' AND cadastro_completo = 1) AS completos
             FROM usuarios"
        )->fetch();

        return array_map('intval', $linha ?: []);
    }

    /** @return array{0: string, 1: array} */
    private function montarFiltro(FiltroUsuariosDTO $filtro): array
    {
        $condicoes = ["u.tipo <> 'admin'"];
        $parametros = [];

        if ($filtro->busca !== null) {
            // Colunas JSON comparam com diferenciação de maiúsculas; o CAST deixa a busca insensível.
            $condicoes[] = '(u.nome LIKE ? OR u.email LIKE ? OR pe.nome_empresa LIKE ? OR pp.cargo_desejado LIKE ?
                OR CAST(pp.stack AS CHAR) COLLATE utf8mb4_unicode_ci LIKE ?
                OR CAST(pe.stacks_contratacao AS CHAR) COLLATE utf8mb4_unicode_ci LIKE ?)';
            $termo = '%' . addcslashes($filtro->busca, '%_\\') . '%';
            array_push($parametros, $termo, $termo, $termo, $termo, $termo, $termo);
        }
        if ($filtro->tipo !== null) {
            $condicoes[] = 'u.tipo = ?';
            $parametros[] = $filtro->tipo->value;
        }
        if ($filtro->senioridade !== null) {
            $condicoes[] = 'pp.senioridade = ?';
            $parametros[] = $filtro->senioridade->value;
        }
        if ($filtro->area !== null) {
            $condicoes[] = 'pp.area = ?';
            $parametros[] = $filtro->area->value;
        }

        return ['WHERE ' . implode(' AND ', $condicoes), $parametros];
    }

    private function buscarUm(string $sql, array $parametros): ?Usuario
    {
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($parametros);
        $linha = $consulta->fetch();
        return $linha ? Usuario::deLinha($linha) : null;
    }
}
