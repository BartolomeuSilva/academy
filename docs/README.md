# Academy — Especificações

Plataforma de treinamentos internos da Semper Technology, publicada em **https://academy.sempertechnology.com.br**.

| # | Documento | Conteúdo |
|---|-----------|----------|
| 1 | [PRD](01-prd.md) | Visão, escopo do MVP, papéis, histórias de usuário e critérios de aceite |
| 2 | [Domínio](02-dominio.md) | Entidades, relacionamentos, regras de negócio, autorização |
| 3 | [Arquitetura](03-arquitetura.md) | Stack, módulos, pipeline de vídeo, armazenamento, filas |
| 4 | [Infraestrutura](04-infraestrutura.md) | Docker, Docker Swarm, Traefik, Postgres, Redis, MinIO, secrets, deploy |
| 5 | [Testes e CI/CD](05-testes-ci-cd.md) | Estratégia de testes, quality gates, pipeline GitHub Actions |
| 6 | [Roadmap](06-roadmap.md) | Fases e entregas incrementais |
| 7 | [Pendências](07-pendencias.md) | Perguntas em aberto, premissas e dependências a aprovar |

## Decisões fechadas

| Tema | Decisão |
|------|---------|
| Propósito | Treinamentos internos; todos os cursos gratuitos |
| Estrutura | Curso → Módulos → Aulas |
| Aula | Videoaula + anexos (imagens e PDF) |
| Vídeo | Encoding próprio (FFmpeg → HLS 480p/720p/1080p), worker de fila com CPU limitada |
| Acesso | Cadastro aberto, sem aprovação; todo aluno logado vê todos os cursos publicados |
| Liberação | Todas as aulas liberadas de uma vez; acesso nunca expira |
| Certificado | Não há |
| Interação | Comentários nas aulas |
| Papéis | Admin e Aluno |
| Frontend | Vue 3 + Inertia v3 + Tailwind 4 (starter kit), temas claro e escuro |
| Banco | PostgreSQL dentro do Swarm |
| Cache/Filas/Sessão | Redis |
| Arquivos | MinIO existente em `storage.ichatbot.com.br`, bucket `academy` |
| E-mail | SMTP `mail.g5d.com.br` |
| Proxy | Traefik já existente no Swarm, rede `network_public` |
| Servidor | VPS única (Swarm de 1 nó), 8 GB RAM, 200 GB disco, compartilhada |
| Vídeos | Típico 300 MB / 10 min; máximo 2 GB / 1 h |
| Primeiro Admin | `contato@bartolomeusilva.com` |
| Repositório | https://github.com/BartolomeuSilva/academy |
| Ambientes | Local (testes) e Produção |
| Repositório | GitHub (CI/CD via GitHub Actions, imagens no GHCR) |
| Qualidade | Nada é publicado sem passar em todos os quality gates |
| Fora de escopo | Observabilidade, backup, LGPD, certificados, pagamentos |
