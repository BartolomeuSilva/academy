# 1. PRD — Product Requirements

## 1.1 Visão

Centralizar os treinamentos internos em videoaulas organizadas por curso, com materiais de apoio e espaço para dúvidas, acessível a qualquer pessoa que crie uma conta.

## 1.2 Papéis

| Papel         | Descrição                                                                                              |
| ------------- | ------------------------------------------------------------------------------------------------------ |
| **Visitante** | Não autenticado. Vê a página inicial, pode se cadastrar e fazer login.                                 |
| **Aluno**     | Papel padrão de todo cadastro. Assiste aulas, baixa anexos, comenta.                                   |
| **Admin**     | Tudo que o aluno faz + gerencia cursos, módulos, aulas, vídeos, anexos, usuários e modera comentários. |

O primeiro Admin (`contato@bartolomeusilva.com`) é criado via comando `php artisan academy:make-admin {email}`. Depois disso, admins promovem/rebaixam usuários pela interface.

## 1.3 Escopo do MVP

### Autenticação e conta (base do starter kit / Fortify)

- Cadastro aberto (nome, e-mail, senha), **com verificação de e-mail** (premissa, ver Pendências).
- Login, logout, recuperação de senha, 2FA e passkeys opcionais (já existem no starter kit).
- Configurações de perfil, senha e aparência (claro / escuro / sistema).

### Área do aluno

- **Catálogo**: lista de cursos publicados (capa, título, descrição curta, nº de aulas, duração total, % de progresso).
- **Página do curso**: descrição, lista de módulos e aulas com duração e indicador de concluída; botão "Continuar de onde parei".
- **Página da aula**: player HLS com troca automática de qualidade e seletor manual, lista lateral do curso, descrição, anexos (visualizar imagem, abrir/baixar PDF), comentários, navegação anterior/próxima.
- **Progresso** (premissa, ver Pendências): retoma o vídeo da última posição; aula marcada como concluída ao atingir 90% assistido ou manualmente.

### Comentários

- Aluno comenta em uma aula e responde comentários (1 nível de resposta).
- Autor edita e exclui o próprio comentário; Admin exclui qualquer comentário.
- Ordenação: mais recentes primeiro, paginação "carregar mais".

### Administração

- **Cursos**: criar, editar, excluir, publicar/despublicar, capa (imagem), ordenar no catálogo.
- **Módulos**: criar, editar, excluir, reordenar (drag & drop).
- **Aulas**: criar, editar, excluir, mover entre módulos, reordenar, publicar/despublicar.
- **Vídeo da aula**: upload direto para o MinIO (multipart, com barra de progresso e retomada), acompanhamento do status (enviando → na fila → processando → pronto / falhou), substituir. O original é apagado após a conversão.
- **Anexos**: upload de imagens (JPG, PNG, WEBP) e PDF, renomear, reordenar, excluir.
- **Usuários**: listar, buscar, promover a Admin / rebaixar a Aluno, bloquear/desbloquear.
- **Comentários**: lista geral recente com filtro por curso, excluir.

### Não funcionais

- Interface em **pt-BR**, responsiva (mobile first), temas claro e escuro.
- Vídeo típico de **~300 MB / 10 min**; limite máximo de **2 GB / 1 h** por aula.
- Página da aula carrega em < 2 s (p95) em produção, excluindo o vídeo.
- Nenhuma URL de arquivo é pública: vídeos, anexos e capas são servidos por URLs assinadas de curta duração, apenas para usuários autenticados.
- Acessibilidade básica: navegação por teclado, contraste AA em ambos os temas, sem erros do `assertNoAccessibilityIssues` nas páginas principais.

## 1.4 Fora de escopo (MVP)

Certificados, pagamentos, matrículas/grupos, liberação gradual, quizzes, aulas ao vivo, notificações por e-mail de comentários, busca full-text, app mobile, observabilidade, backup, LGPD.

## 1.5 Histórias de usuário e critérios de aceite

Cada critério abaixo vira pelo menos um teste automatizado (feature ou browser).

### Aluno

**US-01 — Cadastro**: como visitante quero criar uma conta para acessar os treinamentos.

- Dado nome, e-mail único e senha válida, a conta é criada com papel Aluno e um e-mail de verificação é enviado.
- E-mail duplicado ou senha fraca retorna erro de validação em pt-BR.
- Usuário não verificado é redirecionado para a tela de verificação ao acessar áreas autenticadas.

**US-02 — Catálogo**: como aluno quero ver os cursos disponíveis.

- Somente cursos publicados com ao menos uma aula publicada aparecem.
- Cada card mostra título, capa, nº de aulas publicadas, duração total e meu % de progresso.

**US-03 — Assistir aula**: como aluno quero assistir uma videoaula.

- O player carrega o HLS e permite trocar a qualidade.
- Aula despublicada ou de curso despublicado retorna 404 para aluno.
- Visitante é redirecionado para o login.
- URLs de vídeo expiram (não funcionam após o TTL nem para outra sessão sem nova assinatura).

**US-04 — Progresso**: como aluno quero continuar de onde parei.

- A posição é salva a cada 15 s e ao pausar/sair.
- Ao reabrir a aula, o vídeo inicia na última posição salva.
- Ao passar de 90% da duração a aula fica concluída; posso marcar/desmarcar manualmente.

**US-05 — Anexos**: como aluno quero acessar os materiais da aula.

- Imagens abrem em visualizador; PDFs abrem em nova aba ou são baixados com o nome original.
- O download só funciona autenticado.

**US-06 — Comentar**: como aluno quero tirar dúvidas na aula.

- Posso criar comentário (1–2000 caracteres) e responder a um comentário raiz.
- Não é possível responder a uma resposta (máx. 1 nível).
- Posso editar/excluir somente os meus; comentário editado mostra "editado".
- Limite de 10 comentários por minuto por usuário.

### Admin

**US-10 — Gerenciar cursos**: criar/editar/excluir/publicar cursos com capa.

- Slug gerado do título, único, editável.
- Excluir curso exige confirmação e remove módulos, aulas, vídeos e anexos (inclusive arquivos no MinIO, via fila).

**US-11 — Estruturar curso**: criar e reordenar módulos e aulas por drag & drop.

- A nova ordem persiste e reflete imediatamente para os alunos.

**US-12 — Enviar vídeo**: enviar o vídeo de uma aula.

- Upload vai direto do navegador ao MinIO em partes; falha de rede permite retomar.
- Aceita MP4, MOV, MKV, WEBM até 2 GB; o worker rejeita (status "falhou") vídeos com mais de 1 h.
- Após o upload, o status muda para "na fila" e depois "processando"; ao terminar, "pronto" com duração preenchida.
- Em falha, status "falhou" com mensagem; o admin envia o arquivo novamente.
- Aula só pode ser publicada com vídeo "pronto".

**US-13 — Anexos**: anexar imagens e PDFs (até 50 MB cada) a uma aula.

**US-14 — Usuários**: promover/rebaixar/bloquear usuários.

- Admin não pode rebaixar nem bloquear a si mesmo; sempre resta ao menos um Admin.
- Usuário bloqueado é deslogado e não consegue logar.

**US-15 — Moderar comentários**: excluir qualquer comentário; excluir um comentário raiz exclui suas respostas.
