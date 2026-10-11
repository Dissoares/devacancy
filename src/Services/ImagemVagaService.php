<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Idioma;
use App\Entities\Vaga;
use GdImage;

/**
 * Cria a imagem 1200x630 que aparece quando uma vaga é compartilhada (WhatsApp, Telegram, X, Facebook, LinkedIn).
 * Mostra título, nível, local, salário e stack; a empresa nunca aparece (só para assinantes).
 * Desenha em dobro de tamanho e reduz no fim, para os cantos arredondados ficarem suaves.
 */
final class ImagemVagaService
{
    private const LARGURA = 1200;
    private const ALTURA = 630;
    private const ESCALA = 2;

    public function disponivel(): bool
    {
        return extension_loaded('gd') && function_exists('imagettftext') && is_file($this->fonte('Bold'));
    }

    /** Caminho do PNG da vaga (gerado uma vez e guardado), ou null se o servidor não consegue desenhar. */
    public function caminhoDaVaga(Vaga $vaga): ?string
    {
        if (!$this->disponivel()) {
            return null;
        }

        $dados = [
            'titulo' => $vaga->titulo,
            'nivel' => $vaga->senioridade->rotulo(),
            'local' => $vaga->local(),
            'salario' => $vaga->faixaSalarial(),
            'stack' => array_slice($vaga->stack, 0, 5),
            'versao' => 2, // muda quando o desenho muda, para refazer as imagens guardadas
            'rodape' => t('compartilhar.imagem_rodape'),
            'selo' => t('compartilhar.imagem_selo'),
        ];
        $destino = $this->pasta() . '/vaga-' . $vaga->id . '-' . substr(md5(json_encode($dados)), 0, 10) . '.png';

        if (!is_file($destino)) {
            $this->limparAntigas($vaga->id);
            $this->desenhar($dados, $destino);
        }
        return $destino;
    }

    /** Imagem padrão do site (usada na landing e como reserva). Chamada pelo script database/gerar-og.php. */
    public function gerarPadrao(string $destino): void
    {
        $this->desenhar([
            'titulo' => t('compartilhar.imagem_padrao_titulo'),
            'nivel' => null,
            'local' => t('compartilhar.imagem_padrao_texto'),
            'salario' => null,
            'stack' => ['PHP', 'JavaScript', 'Python', 'Java', 'React'],
            'rodape' => t('compartilhar.imagem_rodape_padrao'),
            'selo' => null,
        ], $destino);
    }

    // ---------------------------------------------------------------- Desenho

    /** @param array{titulo: string, nivel: ?string, local: string, salario: ?string, stack: string[], rodape: string, selo: ?string} $d */
    private function desenhar(array $d, string $destino): void
    {
        $e = self::ESCALA;
        $imagem = imagecreatetruecolor(self::LARGURA * $e, self::ALTURA * $e);
        imagealphablending($imagem, true);
        imagesavealpha($imagem, true);

        $this->fundo($imagem);

        $branco = imagecolorallocate($imagem, 255, 255, 255);
        $claro = imagecolorallocate($imagem, 203, 213, 225);
        $azul = imagecolorallocate($imagem, 147, 197, 253);
        $margem = 72;

        // Marca
        $this->retanguloArredondado($imagem, $margem, 56, 56, 56, 14, imagecolorallocate($imagem, 37, 99, 235));
        $this->texto($imagem, '>_', 22, $margem + 9, 94, $branco, 'Bold');
        $this->texto($imagem, 'dev', 32, $margem + 74, 96, $branco, 'Bold');
        $larguraDev = $this->largura('dev', 32, 'Bold');
        $this->texto($imagem, 'acancy', 32, $margem + 74 + $larguraDev, 96, $azul, 'Bold');

        if ($d['selo']) {
            $larguraSelo = $this->largura($d['selo'], 20, 'Medium') + 40;
            $this->retanguloArredondado($imagem, self::LARGURA - $margem - $larguraSelo, 62, $larguraSelo, 44, 22, imagecolorallocate($imagem, 38, 64, 128));
            $this->texto($imagem, $d['selo'], 20, self::LARGURA - $margem - $larguraSelo + 20, 92, $branco, 'Medium');
        }

        // Título (até 3 linhas)
        // Títulos longos (3 linhas) encolhem um pouco para sobrar espaço para os dados abaixo.
        $tamanho = 64;
        $linhas = $this->quebrar($d['titulo'], $tamanho, self::LARGURA - 2 * $margem, 3, 'Bold');
        if (count($linhas) >= 3) {
            $tamanho = 54;
            $linhas = $this->quebrar($d['titulo'], $tamanho, self::LARGURA - 2 * $margem, 3, 'Bold');
        }
        $y = 214;
        foreach ($linhas as $linha) {
            $this->texto($imagem, $linha, $tamanho, $margem, $y, $branco, 'Bold');
            $y += $tamanho + 16;
        }

        // Nível, local e salário (logo abaixo do título; uma linha só se o título já ocupa três)
        $info = array_filter([$d['nivel'], $d['local'], $d['salario']]);
        $y += 8;
        $linhaInfo = implode('   ·   ', $info);
        foreach ($this->quebrar($linhaInfo, 30, self::LARGURA - 2 * $margem, count($linhas) >= 3 ? 1 : 2, 'Medium') as $linha) {
            $this->texto($imagem, $linha, 30, $margem, $y, $claro, 'Medium');
            $y += 44;
        }

        // Stack
        $x = $margem;
        $topo = 470;
        foreach ($d['stack'] as $tecnologia) {
            $largura = $this->largura($tecnologia, 24, 'Medium') + 40;
            if ($x + $largura > self::LARGURA - $margem) {
                break;
            }
            $this->retanguloArredondado($imagem, $x, $topo, $largura, 52, 26, imagecolorallocate($imagem, 58, 98, 203));
            $this->texto($imagem, $tecnologia, 24, $x + 20, $topo + 35, $branco, 'Medium');
            $x += $largura + 14;
        }

        // Rodapé
        $this->texto($imagem, $d['rodape'], 24, $margem, 580, $azul, 'Medium');

        $final = imagecreatetruecolor(self::LARGURA, self::ALTURA);
        imagecopyresampled($final, $imagem, 0, 0, 0, 0, self::LARGURA, self::ALTURA, self::LARGURA * $e, self::ALTURA * $e);
        imagedestroy($imagem);

        $pasta = dirname($destino);
        if (!is_dir($pasta)) {
            mkdir($pasta, 0775, true);
        }
        imagepng($final, $destino, 6);
        imagedestroy($final);
    }

    private function fundo(GdImage $imagem): void
    {
        $largura = imagesx($imagem);
        $altura = imagesy($imagem);
        // Degradê diagonal do azul-marinho ao azul da marca.
        for ($y = 0; $y < $altura; $y++) {
            $t = $y / $altura;
            $cor = imagecolorallocate($imagem, (int) (15 + 22 * $t), (int) (23 + 76 * $t), (int) (42 + 193 * $t));
            imageline($imagem, 0, $y, $largura, $y, $cor);
        }
        // Brilho suave no canto, só para dar profundidade.
        $brilho = imagecolorallocatealpha($imagem, 96, 165, 250, 118);
        imagefilledellipse($imagem, (int) ($largura * 0.9), (int) ($altura * 0.05), (int) ($largura * 0.7), (int) ($largura * 0.7), $brilho);
        $brilho2 = imagecolorallocatealpha($imagem, 249, 115, 22, 120);
        imagefilledellipse($imagem, (int) ($largura * 0.02), (int) ($altura * 1.02), (int) ($largura * 0.45), (int) ($largura * 0.45), $brilho2);
    }

    private function retanguloArredondado(GdImage $imagem, int $x, int $y, int $l, int $a, int $raio, int $cor): void
    {
        [$x, $y, $l, $a, $raio] = array_map(fn (int $v) => $v * self::ESCALA, [$x, $y, $l, $a, $raio]);
        imagefilledrectangle($imagem, $x + $raio, $y, $x + $l - $raio, $y + $a, $cor);
        imagefilledrectangle($imagem, $x, $y + $raio, $x + $l, $y + $a - $raio, $cor);
        foreach ([[$x + $raio, $y + $raio], [$x + $l - $raio, $y + $raio], [$x + $raio, $y + $a - $raio], [$x + $l - $raio, $y + $a - $raio]] as [$cx, $cy]) {
            imagefilledellipse($imagem, $cx, $cy, $raio * 2, $raio * 2, $cor);
        }
    }

    private function texto(GdImage $imagem, string $texto, int $tamanho, int $x, int $y, int $cor, string $peso): void
    {
        imagettftext($imagem, $tamanho * self::ESCALA * 0.75, 0, $x * self::ESCALA, $y * self::ESCALA, $cor, $this->fonte($peso), $texto);
    }

    private function largura(string $texto, int $tamanho, string $peso): int
    {
        $caixa = imagettfbbox($tamanho * self::ESCALA * 0.75, 0, $this->fonte($peso), $texto);
        return (int) ceil(($caixa[2] - $caixa[0]) / self::ESCALA);
    }

    /** Quebra o texto em linhas que cabem na largura; passando do limite de linhas, termina com reticências. @return string[] */
    private function quebrar(string $texto, int $tamanho, int $larguraMaxima, int $maxLinhas, string $peso): array
    {
        $linhas = [];
        $atual = '';
        foreach (preg_split('/\s+/u', trim($texto)) ?: [] as $palavra) {
            $tentativa = $atual === '' ? $palavra : $atual . ' ' . $palavra;
            if ($this->largura($tentativa, $tamanho, $peso) <= $larguraMaxima || $atual === '') {
                $atual = $tentativa;
                continue;
            }
            $linhas[] = $atual;
            $atual = $palavra;
        }
        if ($atual !== '') {
            $linhas[] = $atual;
        }

        if (count($linhas) > $maxLinhas) {
            $linhas = array_slice($linhas, 0, $maxLinhas);
            $ultima = rtrim($linhas[$maxLinhas - 1], " ,.;:-");
            while ($ultima !== '' && $this->largura($ultima . '…', $tamanho, $peso) > $larguraMaxima) {
                $ultima = mb_substr($ultima, 0, -1);
            }
            $linhas[$maxLinhas - 1] = $ultima . '…';
        }
        return $linhas;
    }

    private function fonte(string $peso): string
    {
        return RAIZ . '/storage/fontes/Poppins-' . $peso . '.ttf';
    }

    private function pasta(): string
    {
        return RAIZ . '/storage/cache/og';
    }

    /** Quando a vaga muda, a imagem antiga dela é apagada para não acumular arquivos. */
    private function limparAntigas(int $vagaId): void
    {
        foreach (glob($this->pasta() . '/vaga-' . $vagaId . '-*.png') ?: [] as $arquivo) {
            @unlink($arquivo);
        }
    }
}
