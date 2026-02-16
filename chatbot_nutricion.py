import streamlit as st
import google.generativeai as genai
from dotenv import load_dotenv
import os

# Carga la API key
load_dotenv()
genai.configure(api_key=os.getenv("GOOGLE_API_KEY"))

# Configura el modelo (Gemini 1.5 Flash es rápido y gratis para pruebas)
model = genai.GenerativeModel('gemini-1.5-flash')

# Prompt del sistema para guiar al bot
SYSTEM_PROMPT = """
Eres un asistente de nutrición llamado Dr. NutriBot, creado para responder dudas básicas y superficiales sobre alimentos, productos nutricionales o hábitos generales.
IMPORTANTE: Siempre empieza y termina tus respuestas con este disclaimer: "Recuerda que no soy un sustituto de un profesional de la salud. Consulta a un nutricionista certificado para consejos personalizados."
- Responde de forma amigable, corta y en español.
- Enfócate en información general (ej: beneficios comunes, mitos básicos). No des diagnósticos, planes de dieta ni recomendaciones médicas.
- Si la pregunta es compleja o personal, redirige a un experto.
Ejemplo de respuesta: Para "¿Es bueno el café?", di algo como: "El café en moderación puede ayudar con la energía, pero evita exceso si tienes sensibilidad. [Disclaimer]".
"""

# Inicializa el chat
if "messages" not in st.session_state:
    st.session_state.messages = [{"role": "system", "content": SYSTEM_PROMPT}]

# Interfaz de Streamlit
st.title("🩺 Dr. NutriBot - Asistente de Nutrición")
st.write("¡Hola! Pregúntame sobre alimentos o productos. Recuerda: soy solo para dudas rápidas.")

# Muestra el historial del chat
for message in st.session_state.messages[1:]:  # Salta el system prompt
    with st.chat_message(message["role"]):
        st.markdown(message["content"])

# Entrada del usuario
if prompt := st.chat_input("Escribe tu duda aquí..."):
    # Añade mensaje del usuario
    st.session_state.messages.append({"role": "user", "content": prompt})
    with st.chat_message("user"):
        st.markdown(prompt)

    # Genera respuesta con Gemini
    with st.chat_message("assistant"):
        # Envía el historial completo al modelo
        chat = model.start_chat(history=st.session_state.messages)
        response = chat.send_message(prompt)
        st.markdown(response.text)
    
    # Añade la respuesta al historial
    st.session_state.messages.append({"role": "assistant", "content": response.text})