<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

// Interfaces (Keep these, they're crucial for TypeScript)
interface User { id: number; nome: string; email: string; tipo: 'submissor' | 'avaliador' | 'decisor'; }
interface Discipline { id?: number; nome: string; carga_horaria: number; semestre: number; }
interface StatusEntry { id?: number; status: 'submetida' | 'em_avaliacao' | 'ajustes_requeridos' | 'em_aprovacao' | 'aprovada' | 'rejeitada'; data_status: string; observacao: string; }
interface Proposal {
  id: number; nome: string; carga_horaria_total: number; justificativa: string;
  impacto_social: string; comentario_avaliador: string | null;
  comentario_decisor: string | null; disciplinas: Discipline[]; historico_status: StatusEntry[];
  autor?: User; avaliador?: User; decisorFinal?: User;
}

const API_URL = 'http://127.0.0.1:8000/api';

// States
const isLoggedIn = ref(false);
const currentUser = ref<User | null>(null);
const authEmail = ref('');
const authPassword = ref('');
const authToken = ref(localStorage.getItem('authToken') || '');
const authType = ref<'submissor' | 'avaliador' | 'decisor'>('submissor');
const authName = ref('');

const proposals = ref<Proposal[]>([]);
const newProposal = ref({ nome: '', carga_horaria_total: 0, quantidade_semestres: 0, justificativa: '', impacto_social: '', disciplinas: [{ nome: '', carga_horaria: 0, semestre: 0 }] });
const selectedProposalId = ref<number | null>(null);
const proposalDetails = ref<Proposal | null>(null);
const evaluationComment = ref('');
const evaluationStatus = ref<'ajustes_requeridos' | 'em_aprovacao'>('em_aprovacao');
const decisionComment = ref('');
const decisionStatus = ref<'aprovada' | 'rejeitada'>('aprovada');

const message = ref('');
const error = ref('');
const showAuthModal = ref(false); // Controls the "cover" container for auth
const isRegisterMode = ref(false);

// Functions for UI/API interaction
const setAuthHeader = () => {
  if (authToken.value) axios.defaults.headers.common['Authorization'] = `Bearer ${authToken.value}`;
  else delete axios.defaults.headers.common['Authorization'];
};
const clearMessages = () => { message.value = ''; error.value = ''; };
const handleApiError = (err: any, msg: string) => { clearMessages(); error.value = err.response?.data?.message || msg; if (err.response?.data?.errors) error.value += ' ' + Object.values(err.response.data.errors).flat().join(' '); };

const authRequest = async (path: string, data: any, successMsg: string) => {
  try {
    const res = await axios.post(`${API_URL}/${path}`, data);
    clearMessages();
    message.value = successMsg;
    if (path === 'login') {
      authToken.value = res.data.access_token;
      localStorage.setItem('authToken', authToken.value);
      setAuthHeader();
      currentUser.value = res.data.user as User;
      isLoggedIn.value = true;
      fetchProposals();
    }
    showAuthModal.value = false; // <<< Mantenha esta linha para FECHAR O MODAL APENAS NO SUCESSO
  } catch (err: any) {
    handleApiError(err, `Erro ao ${path === 'register' ? 'registrar' : 'logar'}.`);
    // O MODAL NÃO DEVE FECHAR AQUI EM CASO DE ERRO.
    // O showAuthModal.value permanece true para que o usuário possa tentar novamente.
  }
};

const registerUser = () => authRequest('register', { nome: authName.value, email: authEmail.value, senha: authPassword.value, senha_confirmation: authPassword.value, tipo: authType.value }, 'Registro bem-sucedido!');
const loginUser = () => authRequest('login', { email: authEmail.value, senha: authPassword.value }, 'Login bem-sucedido!');

const logoutUser = async () => {
  try { await axios.post(`${API_URL}/logout`);
    authToken.value = ''; currentUser.value = null; isLoggedIn.value = false;
    localStorage.removeItem('authToken'); setAuthHeader(); clearMessages(); message.value = 'Logout bem-sucedido!';
    proposals.value = []; proposalDetails.value = null;
  } catch (err: any) { handleApiError(err, 'Erro ao fazer logout.'); }
};

const fetchCurrentUser = async () => {
  if (!authToken.value) return;
  try { setAuthHeader(); const res = await axios.get(`${API_URL}/user`);
    currentUser.value = res.data as User; isLoggedIn.value = true;
  } catch (err: any) { if (err.response?.status === 401) logoutUser(); }
};

const submitProposal = async () => {
  try { setAuthHeader(); const res = await axios.post(`${API_URL}/propostas`, newProposal.value);
    clearMessages(); message.value = res.data.message; fetchProposals();
    newProposal.value = { nome: '', carga_horaria_total: 0, quantidade_semestres: 0, justificativa: '', impacto_social: '', disciplinas: [{ nome: '', carga_horaria: 0, semestre: 0 }] }; // Clear form
  } catch (err: any) { handleApiError(err, 'Erro ao submeter proposta.'); }
};

const fetchProposals = async () => {
  try { setAuthHeader(); const res = await axios.get<{'propostas': Proposal[]}>(`${API_URL}/propostas`);
    proposals.value = res.data.propostas; clearMessages(); message.value = 'Propostas carregadas!';
  } catch (err: any) { handleApiError(err, 'Erro ao carregar propostas.'); }
};

const fetchProposalDetails = async () => {
  if (selectedProposalId.value === null) { proposalDetails.value = null; return; }
  try { setAuthHeader(); const res = await axios.get<{'proposta': Proposal}>(`${API_URL}/propostas/${selectedProposalId.value}`);
    proposalDetails.value = res.data.proposta; clearMessages(); message.value = 'Detalhes carregados!';
  } catch (err: any) { handleApiError(err, 'Erro ao carregar detalhes.'); }
};

const evaluateProposal = async () => {
  if (selectedProposalId.value === null) { error.value = 'Selecione uma proposta para avaliar.'; return; }
  try { setAuthHeader(); const res = await axios.put(`${API_URL}/propostas/${selectedProposalId.value}/avaliar`, { comentario: evaluationComment.value, status_novo: evaluationStatus.value });
    clearMessages(); message.value = res.data.message; fetchProposalDetails(); fetchProposals();
  } catch (err: any) { handleApiError(err, 'Erro ao avaliar proposta.'); }
};

const decideProposal = async () => {
  if (selectedProposalId.value === null) { error.value = 'Selecione uma proposta para decidir.'; return; }
  try { setAuthHeader(); const res = await axios.put(`${API_URL}/propostas/${selectedProposalId.value}/decidir`, { comentario: decisionComment.value, status_final: decisionStatus.value });
    clearMessages(); message.value = res.data.message; fetchProposalDetails(); fetchProposals();
  } catch (err: any) { handleApiError(err, 'Erro ao decidir proposta.'); }
};

// Lifecycle
onMounted(() => {
  setAuthHeader();
  fetchCurrentUser();
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') showAuthModal.value = false;
  });
});
</script>

<template>
  <div style="display: flex; justify-content: center; align-items: flex-start; padding: 20px; border-radius: px;box-sizing: border-box; font-family: sans-serif; background-color: #333;">
    <div style="background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 800px; width: 100%;">
      <h1 style="text-align: center; margin-bottom: 20px; color: black;">Sistema PPC</h1>

      <div v-if="message" style="color: black; text-align: center; margin-bottom: 15px; border: 1px solid black; padding: 10px; background-color: #ddd;">{{ message }}</div>
      <div v-if="error" style="color: black; text-align: center; margin-bottom: 15px; border: 1px solid black; padding: 10px; background-color: #eee;">{{ error }}</div>

      <div v-if="!isLoggedIn" style="text-align: center; margin-top: 20px;">
        <button @click="showAuthModal = true; isRegisterMode = false" style="padding: 10px 20px; margin-right: 15px; background-color: #555; color: white; border: none; border-radius: 4px; cursor: pointer;">Login</button>
        <button @click="showAuthModal = true; isRegisterMode = true" style="padding: 10px 20px; background-color: #ccc; color: black; border: none; border-radius: 4px; cursor: pointer;">Criar Conta</button>
      </div>
      <div v-else style="text-align: center; margin-top: 20px;">
        <p style="color: black;">Olá, <strong>{{ currentUser?.nome }}</strong> ({{ currentUser?.tipo }})</p>
        <button @click="logoutUser" style="padding: 8px 15px; background-color: #555; color: white; border: none; border-radius: 4px; cursor: pointer;">Sair</button>

        <hr style="margin: 30px 0; border-top: 1px solid #ddd;">

        <div style="text-align: left;">
          <h2 style="text-align: center; margin-bottom: 20px; color: black;">Funcionalidades do Sistema</h2>

          <div v-if="currentUser?.tipo === 'submissor'" style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
            <h3 style="margin-top: 0; color: black;">Submeter Nova Proposta</h3>
            <div style="margin-bottom: 10px;"><label style="color: black;">Nome do Curso: <br><input type="text" v-model="newProposal.nome" style="width: calc(100% - 16px); padding: 8px; border: 1px solid #ccc; background-color: #eee; color: black;" /></label></div>
            <div style="margin-bottom: 10px;"><label style="color: black;">Carga Horária Total: <br><input type="number" v-model="newProposal.carga_horaria_total" min="1" style="width: calc(100% - 16px); padding: 8px; border: 1px solid #ccc; background-color: #eee; color: black;" /></label></div>
            <div style="margin-bottom: 10px;"><label style="color: black;">Semestres: <br><input type="number" min="1" v-model="newProposal.quantidade_semestres" style="width: calc(100% - 16px); padding: 8px; border: 1px solid #ccc; background-color: #eee; color: black;" /></label></div>
            <div style="margin-bottom: 10px;"><label style="color: black;">Justificativa: <br><textarea v-model="newProposal.justificativa" style="width: calc(100% - 16px); padding: 8px; min-height: 60px; border: 1px solid #ccc; background-color: #eee; color: black;"></textarea></label></div>
            <div style="margin-bottom: 10px;"><label style="color: black;">Impacto Social: <br><textarea v-model="newProposal.impacto_social" style="width: calc(100% - 16px); padding: 8px; min-height: 60px; border: 1px solid #ccc; background-color: #eee; color: black;"></textarea></label></div>

            <h4 style="margin-top: 15px; color: black;">Disciplinas:</h4>
            <div v-for="(d, i) in newProposal.disciplinas" :key="i" style="border: 1px dashed #ccc; padding: 10px; margin-bottom: 10px; background-color: #f8f8f8;">
              <div style="margin-bottom: 5px;"><label style="color: black;">Nome: <br><input type="text" v-model="d.nome" style="width: calc(100% - 16px); padding: 8px; border: 1px solid #ccc; background-color: #eee; color: black;" /></label></div>
              <div style="margin-bottom: 5px;"><label style="color: black;">Carga Horária: <br><input type="number" min="1" v-model="d.carga_horaria" style="width: calc(100% - 16px); padding: 8px; border: 1px solid #ccc; background-color: #eee; color: black;" /></label></div>
              <div style="margin-bottom: 5px;"><label style="color: black;">Semestre: <br><input type="number" min="1" v-model="d.semestre" style="width: calc(100% - 16px); padding: 8px; border: 1px solid #ccc; background-color: #eee; color: black;" /></label></div>
              <button @click="newProposal.disciplinas.splice(i, 1)" style="background-color: #555; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer;">Remover</button>
            </div>
            <button @click="newProposal.disciplinas.push({ nome: '', carga_horaria: 0, semestre: 0 })" style="background-color: #ccc; color: black; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">
              Adicionar Disciplina
            </button>
            <button @click="submitProposal" style="background-color: #555; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">Submeter Proposta</button>
          </div>

          <div style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
            <h3 style="margin-top: 0; color: black;">Lista de Propostas</h3>
            <button @click="fetchProposals" style="background-color: #ccc; color: black; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 10px;">Atualizar Lista</button>
            <ul v-if="proposals.length" style="list-style: none; padding: 0;">
              <li v-for="p in proposals" :key="p.id" @click="selectedProposalId = p.id; fetchProposalDetails()" style="border: 1px solid #eee; padding: 10px; margin-bottom: 5px; background-color: #f8f8f8; color: black; cursor: pointer;">
                <strong>ID: {{ p.id }}</strong> - {{ p.nome }} (Status: {{ p.historico_status[p.historico_status.length - 1].status }})
              </li>
            </ul>
            <p v-else style="color: black;">Nenhuma proposta encontrada.</p>
          </div>

          <div style="border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
            <h3 style="margin-top: 0; color: black;">Detalhes e Ações da Proposta</h3>
            <div style="margin-bottom: 10px;"><label style="color: black;">ID da Proposta: <br><input type="number" min="1" v-model.number="selectedProposalId" @input="fetchProposalDetails" style="width: calc(100% - 16px); padding: 8px; border: 1px solid #ccc; background-color: #eee; color: black;" /></label></div>

            <div v-if="proposalDetails">
              <h4 style="margin-top: 15px; color: black;">Proposta ID: {{ proposalDetails.id }}</h4>
              <p style="color: black;"><strong>Nome:</strong> {{ proposalDetails.nome }}</p>
              <p style="color: black;"><strong>Status Atual:</strong> {{ proposalDetails.historico_status[proposalDetails.historico_status.length - 1].status }}</p>
              <p style="color: black;"><strong>Autor:</strong> {{ proposalDetails.autor?.nome }} ({{ proposalDetails.autor?.email }})</p>
              <p v-if="proposalDetails.avaliador" style="color: black;"><strong>Avaliador:</strong> {{ proposalDetails.avaliador?.nome }}</p>
              <p v-if="proposalDetails.comentario_avaliador" style="color: black;"><strong>Comentário Avaliador:</strong> {{ proposalDetails.comentario_avaliador }}</p>
              <p v-if="proposalDetails.decisorFinal" style="color: black;"><strong>Decisor:</strong> {{ proposalDetails.decisorFinal?.nome }}</p>
              <p v-if="proposalDetails.comentario_decisor" style="color: black;"><strong>Comentário Decisor:</strong> {{ proposalDetails.comentario_decisor }}</p>

              <h5 style="margin-top: 15px; color: black;">Disciplinas:</h5>
              <ul style="list-style: none; padding: 0;">
                <li v-for="d in proposalDetails.disciplinas" :key="d.id" style="border: 1px dashed #eee; padding: 8px; margin-bottom: 5px; background-color: #f8f8f8; color: black;">
                  {{ d.nome }} ({{ d.carga_horaria }}h, Semestre {{ d.semestre }})
                </li>
              </ul>

              <h5 style="margin-top: 15px; color: black;">Histórico de Status:</h5>
              <ul style="list-style: none; padding: 0;">
                <li v-for="s in proposalDetails.historico_status" :key="s.id" style="border: 1px dashed #eee; padding: 8px; margin-bottom: 5px; background-color: #f8f8f8; color: black;">
                  {{ s.status }} em {{ new Date(s.data_status).toLocaleString() }} (Obs: {{ s.observacao }})
                </li>
              </ul>

              <div v-if="currentUser?.tipo === 'avaliador'" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
                <h4 style="margin-top: 0; color: black;">Avaliar Proposta</h4>
                <div style="margin-bottom: 10px;"><label style="color: black;">Comentário: <br><textarea v-model="evaluationComment" style="width: calc(100% - 16px); padding: 8px; min-height: 60px; border: 1px solid #ccc; background-color: #eee; color: black;"></textarea></label></div>
                <div style="margin-bottom: 10px;"><label style="color: black;">Status: <br>
                  <select v-model="evaluationStatus" style="width: calc(100% - 16px); padding: 8px; border: 1px solid #ccc; background-color: #eee; color: black;">
                    <option value="ajustes_requeridos">Ajustes Requeridos</option>
                    <option value="em_aprovacao">Encaminhar p/ Aprovação</option>
                  </select>
                </label></div>
                <button @click="evaluateProposal" style="background-color: #555; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">Avaliar</button>
              </div>

              <div v-if="currentUser?.tipo === 'decisor'" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
                <h4 style="margin-top: 0; color: black;">Decidir Proposta</h4>
                <div style="margin-bottom: 10px;"><label style="color: black;">Comentário: <br><textarea v-model="decisionComment" style="width: calc(100% - 16px); padding: 8px; min-height: 60px; border: 1px solid #ccc; background-color: #eee; color: black;"></textarea></label></div>
                <div style="margin-bottom: 10px;"><label style="color: black;">Decisão Final: <br>
                  <select v-model="decisionStatus" style="width: calc(100% - 16px); padding: 8px; border: 1px solid #ccc; background-color: #eee; color: black;">
                    <option value="aprovada">Aprovar</option>
                    <option value="rejeitada">Rejeitar</option>
                  </select>
                </label></div>
                <button @click="decideProposal" style="background-color: #555; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">Decidir</button>
              </div>
            </div>
            <p v-else style="color: black;">Use o ID acima para ver detalhes da proposta.</p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showAuthModal" @click.self="showAuthModal = false" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); display: flex; justify-content: center; align-items: center; z-index: 1000;">
      <div style="background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); width: 90%; max-width: 400px; text-align: center;">
        <h2 style="margin-top: 0; color: black;">{{ isRegisterMode ? 'Criar Nova Conta' : 'Entrar no Sistema' }}</h2>
        <div style="margin-bottom: 15px;">
          <label style="color: black;">Email: <br><input type="email" v-model="authEmail" style="width: calc(100% - 16px); padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #eee; color: black;" /></label>
        </div>
        <div style="margin-bottom: 15px;">
          <label style="color: black;">Senha: <br><input type="password" v-model="authPassword" style="width: calc(100% - 16px); padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #eee; color: black;" /></label>
        </div>

        <template v-if="isRegisterMode">
          <div style="margin-bottom: 15px;">
            <label style="color: black;">Nome: <br><input type="text" v-model="authName" style="width: calc(100% - 16px); padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #eee; color: black;" /></label>
          </div>
          <div style="margin-bottom: 20px;">
            <label style="color: black;">Tipo: <br>
              <select v-model="authType" style="width: calc(100% - 16px); padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #eee; color: black;">
                <option value="submissor">Submissor</option>
                <option value="avaliador">Avaliador</option>
                <option value="decisor">Decisor</option>
              </select>
            </label>
          </div>
          <button @click="registerUser" style="padding: 10px 20px; background-color: #ccc; color: black; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">Registrar</button>
        </template>
        <template v-else>
          <button @click="loginUser" style="padding: 10px 20px; background-color: #555; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">Login</button>
        </template>
        <button @click="showAuthModal = false" style="padding: 10px 20px; background-color: #666; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancelar</button>
      </div>
    </div>
  </div>
</template>

<style>
/* Reset basic styles for the entire page */
body {
  margin: 0;
  font-family: sans-serif;
}

/* Ensure buttons have a pointer cursor */
button {
  cursor: pointer;
}

/* Minimal styling for labels to ensure inputs are on a new line */
label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}
</style>