# 6. Roadmap

Cada fase termina com tudo testado, todos os gates verdes e deploy em produção. Sem prazo definido; as fases são sequenciais e cada item é um PR.

## Fase 0 — Fundação
- [ ] `git init`, repositório no GitHub, proteção do `main`
- [ ] Remover Teams do starter kit (models, migrations, rotas, páginas, testes)
- [ ] `UserRole` + `blocked_at`, middlewares `admin` e `not-blocked`, comando `academy:make-admin`
- [ ] pt-BR: `APP_LOCALE`, `lang/pt_BR`, tradução das telas do starter kit
- [ ] Dependências aprovadas: Horizon, flysystem S3, Pest Browser, Playwright, hls.js
- [ ] `Dockerfile` multi-stage + `compose.yaml` local (Postgres, Redis, MinIO, Mailpit)
- [ ] Testes rodando em Postgres; CI completo (doc 5)
- [ ] `deploy/stack.yml`, secrets na VPS, limites de recursos ajustados à VPS
- [ ] **Primeiro deploy** em `academy.sempertechnology.com.br` (login/cadastro funcionando) — valida toda a esteira cedo

## Fase 1 — Administração de conteúdo
- [ ] Migrations, models, factories e enums: courses, modules, lessons, lesson_attachments
- [ ] CRUD de cursos com capa e publicação (US-10)
- [ ] Módulos e aulas com reordenação drag & drop (US-11)
- [ ] Anexos de imagem e PDF (US-13)
- [ ] Exclusão em cascata com limpeza no MinIO

## Fase 2 — Pipeline de vídeo
- [ ] Model `Video`, multipart upload direto ao MinIO com retomada (US-12)
- [ ] `EncodeVideo` com FFmpeg → HLS, poster, duração
- [ ] Serviço `video-worker` com limites de CPU no Swarm
- [ ] Status com polling no admin, reprocessar, substituir vídeo
- [ ] Endpoint de stream com playlists reescritas e URLs assinadas

## Fase 3 — Experiência do aluno
- [ ] Catálogo (US-02), página do curso, página da aula com hls.js (US-03)
- [ ] Anexos para o aluno (US-05)
- [ ] Progresso e "continuar de onde parei" (US-04)
- [ ] Testes de browser do fluxo completo, claro/escuro, mobile

## Fase 4 — Comentários
- [ ] Comentários e respostas com rate limit (US-06)
- [ ] Moderação no admin (US-15)

## Fase 5 — Gestão e polimento
- [ ] Gestão de usuários: promover, rebaixar, bloquear (US-14)
- [ ] Revisão de acessibilidade e performance (N+1, índices, cache de playlists)
- [ ] Ajustes de UX a partir do uso real

## Pós-MVP (candidatos)
Notificação de respostas por e-mail, busca, backup do Postgres, painel de métricas de conclusão por curso, legendas (WebVTT).
