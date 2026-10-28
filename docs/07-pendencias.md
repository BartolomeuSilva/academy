# 7. Pendências

## 7.1 Respondidas

| Tema            | Resposta                                                              |
| --------------- | --------------------------------------------------------------------- |
| MinIO           | `https://storage.ichatbot.com.br`; bucket `academy` pode ser criado   |
| Rede do Traefik | `network_public`                                                      |
| VPS             | 8 GB RAM, 200 GB disco, compartilhada com outros apps                 |
| Vídeos          | Típico 300 MB / 10 min; precisa suportar até 1 h (limite: 2 GB / 1 h) |
| Primeiro Admin  | `contato@bartolomeusilva.com`                                         |
| SMTP            | Host `mail.g5d.com.br`                                                |
| Repositório     | https://github.com/BartolomeuSilva/academy                            |

## 7.2 Ainda em aberto

| #   | Pergunta                                                                                                                                                            | Impacto                                             | Necessário em                                                |
| --- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------- | ------------------------------------------------------------ |
| 1   | Nome do **entrypoint HTTPS** e do **certresolver** do Traefik (ver labels de outro serviço: `docker service inspect <serviço> --format '{{json .Spec.Labels}}'`)    | Labels do `stack.yml`                               | Primeiro deploy                                              |
| 2   | Quantas **vCPUs** a VPS tem?                                                                                                                                        | Limite de CPU do `video-worker` e tempo de encoding | Fase 2                                                       |
| 3   | O MinIO roda no mesmo Swarm? Se sim, em qual rede/serviço (para acesso interno sem sair pela internet)?                                                             | Latência e tráfego do worker                        | Fase 2 (opcional)                                            |
| 4   | SMTP: porta, criptografia (TLS/SSL), usuário e remetente (`MAIL_FROM_ADDRESS`)                                                                                      | E-mails de verificação/reset                        | Primeiro deploy (vão como secrets, não precisam vir no chat) |
| 5   | **Original do vídeo**: manter (permite reprocessar, ~770 MB/10 min) ou apagar após encoding (~470 MB/10 min)? Ver [§4.7](04-infraestrutura.md#47-capacidade-da-vps) | Capacidade de disco                                 | Fase 2                                                       |

## 7.3 Premissas (confirmadas em 27/10/2026)

| #   | Premissa                                                                                                                 |
| --- | ------------------------------------------------------------------------------------------------------------------------ |
| P1  | Cadastro exige **verificação de e-mail** (já ativa no Fortify). Como o cadastro é aberto, evita contas com e-mail falso. |
| P2  | **Progresso do aluno** entra no MVP (retomar posição + aula concluída).                                                  |
| P3  | **Teams** do starter kit será removido; **2FA e passkeys** continuam disponíveis como opcionais.                         |
| P4  | Interface somente em pt-BR.                                                                                              |
| P5  | Limites: vídeo até 2 GB / 1 h; anexo até 50 MB; comentário até 2000 caracteres.                                          |

## 7.4 Dependências novas (aprovadas em 27/10/2026)

| Pacote                          | Tipo           | Motivo                                           |
| ------------------------------- | -------------- | ------------------------------------------------ |
| `laravel/horizon`               | Composer       | Gerenciar filas Redis e painel de jobs           |
| `league/flysystem-aws-s3-v3`    | Composer       | Disco S3/MinIO no Laravel                        |
| `pestphp/pest-plugin-browser`   | Composer (dev) | Testes de browser                                |
| `playwright`                    | npm (dev)      | Motor dos testes de browser                      |
| `hls.js`                        | npm            | Reprodução HLS em navegadores sem suporte nativo |
| `vuedraggable` (ou equivalente) | npm            | Drag & drop para reordenar módulos e aulas       |

Imagens Docker: `serversideup/php:8.5-fpm-nginx`, `postgres:17-alpine`, `redis:7-alpine`, `minio/minio` (só local), `axllent/mailpit` (só local).
