# Informações Acerca do Projeto:

## Sistema PPC - Plano Pedagógico do Curso

   - O **Protótipo de Sistema WEB** é um projeto desenvolvido como parte do processo seletivo para estágio na Assessoria Técnica de Tecnologia da Informação e Comunicação (ASTTIC/PROEG) da Universidade Federal do Pará e tem como objetivo permitir:

## Submissão de propostas de cursos por unidades acadêmicas, contendo:
  
  - Nome do curso
  - Carga horária total
  - Quantidade de semestres
  - Justificativa da criação do curso
  - Impacto social
  - Lista de disciplinas, distribuídas por semestre, com nomes e cargas horárias

## Avaliação da proposta por um servidor designado (avaliador), que poderá:

   - Inserir comentários e recomendações
   - Enviar a proposta de volta à unidade
   - Encaminhá-la à Câmara de Ensino

## Decisão final da Câmara de Ensino, que poderá:

   - Aprovar ou reprovar a proposta

## Tecnologias Utilizadas:

- **Laravel** (PHP) – Backend e API
- **Vue.js** – Frontend
- **MySQL** – Banco de dados
- **Git/GitHub** – Controle de versão

## Guia para Execução do Projeto:

# Pré-requisitos

- Antes de começar, certifique-se de ter o seguinte instalado no seu computador:

- Node.js (versão 18 ou superior) e npm (gerenciador de pacotes do Node.js).

- PHP (versão 8.1 ou superior) e Composer (gerenciador de dependências PHP).

- Um servidor de banco de dados MySQL em execução (por exemplo, via XAMPP, WAMP, Docker ou instalação direta).

# Configuração do Backend (Laravel API)

- Clone o Repositório do Backend:

```

   git clone https://github.com/Gabriel8Kbps/Desafio-Pratico-ASTTIC.git
   cd sistema_ppc # Navegue para a pasta clonada

```

- Instale as Dependências do Laravel:

```

   composer install

```

- Configure o Ambiente:

```

   cp .env.example .env

```

Abra o arquivo .env e configure as seguintes linhas com seus dados:

```

DB_DATABASE=seu_nome_de_banco
DB_USERNAME=seu_usuario_mysql
DB_PASSWORD=sua_senha_mysql

```

- Gere a Chave da Aplicação:

```
php artisan key:generate
```

- Execute as Migrações e Semeie o Banco:

```
php artisan migrate
php artisan db:seed # Caso queira adicionar dados de teste
```

- Inicie o Servidor Laravel:

```
php artisan serve
```
Mantenha este terminal aberto enquanto usa o sistema.

# Configuração do Frontend (Vue.js)

- Inicie um novo terminal
- Mude para a pasta de aplicação

```
cd sistema_ppc_frontend
npm install
npm install axios
```

- Inicie o Servidor de Desenvolvimento Vue.js:

```
npm run dev
```

# Acessando o Sistema

- Com ambos os servidores (backend e frontend) rodando, abra o navegador e acesse o endereço frontend:

```

http://localhost:5173

```

# Funcionalidades Essenciais

- Submissão de Propostas (tipo: submissor)

Propriedades da Proposta: Nome do curso, carga horária total, quantidade de semestres, justificativa da criação do curso, impacto social. Inclui grade curricular com disciplinas (nome e carga horária) distribuídas por semestre.

Permissão: Apenas submissores podem elaborar e submeter propostas.

- Avaliação da Proposta (tipo: avaliador)

Ações de Avaliação: Inserir comentários e recomendações.

Decisões de Avaliação: Enviar a proposta de volta à unidade de origem para alterações ou encaminhá-la à Câmara de Ensino.

Permissão: Servidores designados (avaliadores) realizam a avaliação.

- Decisão Final da Câmara de Ensino (tipo: decisor)

Ações de Decisão: Aprovar ou reprovar a proposta.

Permissão: A Câmara de Ensino toma a decisão final.

# Estrutura do Banco de Dados e Lógica de Relacionamentos

O sistema utiliza um banco de dados MySQL, projetado para armazenar e gerenciar eficientemente as propostas de cursos e o fluxo de avaliação. A lógica de relacionamento entre as tabelas é baseada nos requisitos do Plano Pedagógico de Curso (PPC).

- Tabela usuarios

Armazena informações sobre todos os usuários que interagem com o sistema, diferenciando-os por tipo.

Campos Chave: id (chave primária), email (único).

Perfis (tipo):

submissor: Unidades acadêmicas que criam propostas de cursos.
avaliador: Servidores designados que avaliam as propostas.
decisor: Membros da Câmara de Ensino que aprovam ou reprovam as propostas.

Relacionamentos: Um usuário pode ser autor, avaliador ou decisor final de múltiplas propostas de curso.

- Tabela propostas_curso

Contém os detalhes de cada proposta de curso submetida pelas unidades acadêmicas.

Campos Chave: id (chave primária), id_autor, id_avaliador, id_decisor_final (chaves estrangeiras para usuarios.id).

Atributos Principais: Nome do curso, carga horária total, quantidade de semestres, justificativa da criação, e impacto social.

Comentários: Possui campos para comentario_avaliador e comentario_decisor.

Relacionamentos:

Um para Muitos (1:N) com disciplinas: Uma proposta de curso pode ter várias disciplinas.
Um para Muitos (1:N) com status_proposta_curso: Uma proposta de curso pode ter múltiplos registros de status, formando um histórico.
Muitos para Um (N:1) com usuarios: Uma proposta é criada por um id_autor, avaliada por um id_avaliador e decidida por um id_decisor_final.


- Tabela disciplinas
Detalha cada disciplina que compõe a grade curricular de uma proposta de curso.

Campos Chave: id (chave primária), id_curso (chave estrangeira para propostas_curso.id).

Atributos Principais: Nome da disciplina, carga horária e semestre.

Relacionamentos:

Muitos para Um (N:1) com propostas_curso: Uma disciplina pertence a uma única proposta de curso.

- Tabela status_proposta_curso

Registra o histórico e a progressão de cada proposta de curso através do fluxo de avaliação.

Campos Chave: id (chave primária), id_proposta (chave estrangeira para propostas_curso.id).

Atributos Principais: status (Enum: submetida, em_avaliacao, ajustes_requeridos, em_aprovacao, aprovada, rejeitada), data_status, observacao.

Lógica de Fluxo: A status_proposta_curso é atualizada em cada etapa do processo: submissão , avaliação (recomendação de alterações ou envio à Câmara) , e decisão final (aprovação ou reprovação).

Relacionamentos:

Muitos para Um (N:1) com propostas_curso: Cada registro de status está ligado a uma única proposta de curso.

Autor: Gabriel J. S. Costa
