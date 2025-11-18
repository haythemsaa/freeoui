import React, {useState, useRef, useEffect} from 'react';
import {
  View,
  Text,
  StyleSheet,
  TextInput,
  TouchableOpacity,
  FlatList,
  KeyboardAvoidingView,
  Platform,
  ActivityIndicator,
} from 'react-native';
import {Colors} from '../../constants/colors';
import {Theme} from '../../constants/theme';
import Icon from 'react-native-vector-icons/Ionicons';

interface Message {
  id: string;
  text: string;
  isUser: boolean;
  timestamp: Date;
  suggestions?: string[];
}

const ChatbotScreen = () => {
  const [messages, setMessages] = useState<Message[]>([
    {
      id: '1',
      text: 'Bonjour! 👋 Je suis votre assistant FreeOui. Comment puis-je vous aider aujourd\'hui?',
      isUser: false,
      timestamp: new Date(),
      suggestions: [
        'Avantages à proximité',
        'Mes points',
        'Comment utiliser un avantage',
        'Contacter un commerçant',
      ],
    },
  ]);
  const [inputText, setInputText] = useState('');
  const [loading, setLoading] = useState(false);
  const flatListRef = useRef<FlatList>(null);

  useEffect(() => {
    // Auto scroll to bottom when new message
    flatListRef.current?.scrollToEnd({animated: true});
  }, [messages]);

  const handleSend = async (text?: string) => {
    const messageText = text || inputText.trim();
    if (!messageText) return;

    const userMessage: Message = {
      id: Date.now().toString(),
      text: messageText,
      isUser: true,
      timestamp: new Date(),
    };

    setMessages(prev => [...prev, userMessage]);
    setInputText('');
    setLoading(true);

    // Simulate API call to chatbot
    setTimeout(() => {
      const botResponse = getBotResponse(messageText);
      const botMessage: Message = {
        id: (Date.now() + 1).toString(),
        text: botResponse.text,
        isUser: false,
        timestamp: new Date(),
        suggestions: botResponse.suggestions,
      };
      setMessages(prev => [...prev, botMessage]);
      setLoading(false);
    }, 1000);
  };

  const getBotResponse = (userMessage: string): {text: string; suggestions?: string[]} => {
    const msg = userMessage.toLowerCase();

    if (msg.includes('proximité') || msg.includes('près')) {
      return {
        text: 'Je peux vous aider à trouver des avantages près de vous! 📍\n\nActivez votre géolocalisation et rendez-vous sur la carte pour voir tous les commerçants participants autour de vous.',
        suggestions: ['Voir la carte', 'Avantages populaires', 'Catégories'],
      };
    }

    if (msg.includes('points') || msg.includes('solde')) {
      return {
        text: 'Vous pouvez consulter votre solde de points dans votre profil. 🏆\n\nGagnez plus de points en:\n• Utilisant des avantages\n• Relevant des challenges\n• Parrainant des amis',
        suggestions: ['Voir mon profil', 'Challenges actifs', 'Code parrainage'],
      };
    }

    if (msg.includes('utiliser') || msg.includes('comment')) {
      return {
        text: 'Pour utiliser un avantage:\n\n1️⃣ Trouvez un avantage qui vous intéresse\n2️⃣ Appuyez sur "Utiliser"\n3️⃣ Présentez le QR code au commerçant\n4️⃣ Le commerçant scanne et valide\n5️⃣ Profitez de votre réduction! 🎉',
        suggestions: ['Découvrir les avantages', 'Scanner un QR code'],
      };
    }

    if (msg.includes('commerçant') || msg.includes('contact')) {
      return {
        text: 'Pour contacter un commerçant:\n\n• Consultez sa fiche dans l\'avantage\n• Appelez directement depuis l\'app\n• Obtenez l\'itinéraire sur la carte',
        suggestions: ['Voir les commerçants', 'Carte'],
      };
    }

    return {
      text: 'Je n\'ai pas bien compris votre question. 🤔\n\nPouvez-vous reformuler ou choisir un sujet ci-dessous?',
      suggestions: [
        'Avantages à proximité',
        'Mes points',
        'Comment utiliser un avantage',
        'Challenges',
      ],
    };
  };

  const renderMessage = ({item}: {item: Message}) => {
    return (
      <View
        style={[
          styles.messageContainer,
          item.isUser ? styles.userMessage : styles.botMessage,
        ]}>
        {!item.isUser && (
          <View style={styles.botAvatar}>
            <Icon name="chatbubble-ellipses" size={20} color={Colors.primary} />
          </View>
        )}
        <View
          style={[
            styles.messageBubble,
            item.isUser ? styles.userBubble : styles.botBubble,
          ]}>
          <Text
            style={[
              styles.messageText,
              item.isUser && styles.userMessageText,
            ]}>
            {item.text}
          </Text>
          {item.suggestions && (
            <View style={styles.suggestionsContainer}>
              {item.suggestions.map((suggestion, index) => (
                <TouchableOpacity
                  key={index}
                  style={styles.suggestionChip}
                  onPress={() => handleSend(suggestion)}>
                  <Text style={styles.suggestionText}>{suggestion}</Text>
                </TouchableOpacity>
              ))}
            </View>
          )}
        </View>
      </View>
    );
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      keyboardVerticalOffset={90}>
      <FlatList
        ref={flatListRef}
        data={messages}
        renderItem={renderMessage}
        keyExtractor={item => item.id}
        contentContainerStyle={styles.messagesList}
        onContentSizeChange={() => flatListRef.current?.scrollToEnd()}
      />

      {loading && (
        <View style={styles.typingIndicator}>
          <ActivityIndicator size="small" color={Colors.primary} />
          <Text style={styles.typingText}>L'assistant écrit...</Text>
        </View>
      )}

      <View style={styles.inputContainer}>
        <TextInput
          style={styles.input}
          value={inputText}
          onChangeText={setInputText}
          placeholder="Écrivez votre message..."
          placeholderTextColor={Colors.textLight}
          multiline
          maxLength={500}
        />
        <TouchableOpacity
          style={[styles.sendButton, !inputText.trim() && styles.sendButtonDisabled]}
          onPress={() => handleSend()}
          disabled={!inputText.trim()}>
          <Icon
            name="send"
            size={20}
            color={inputText.trim() ? '#FFF' : Colors.textLight}
          />
        </TouchableOpacity>
      </View>
    </KeyboardAvoidingView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.background,
  },
  messagesList: {
    padding: Theme.spacing.md,
    paddingBottom: Theme.spacing.lg,
  },
  messageContainer: {
    flexDirection: 'row',
    marginBottom: Theme.spacing.md,
  },
  userMessage: {
    justifyContent: 'flex-end',
  },
  botMessage: {
    justifyContent: 'flex-start',
  },
  botAvatar: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: Colors.primaryLight,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: Theme.spacing.sm,
  },
  messageBubble: {
    maxWidth: '75%',
    padding: Theme.spacing.md,
    borderRadius: Theme.borderRadius.lg,
  },
  userBubble: {
    backgroundColor: Colors.primary,
    borderBottomRightRadius: 4,
  },
  botBubble: {
    backgroundColor: '#FFF',
    borderBottomLeftRadius: 4,
    ...Theme.shadows.sm,
  },
  messageText: {
    fontSize: Theme.fontSize.md,
    color: Colors.text,
    lineHeight: 20,
  },
  userMessageText: {
    color: '#FFF',
  },
  suggestionsContainer: {
    marginTop: Theme.spacing.sm,
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  suggestionChip: {
    backgroundColor: Colors.primaryLight,
    paddingHorizontal: Theme.spacing.sm,
    paddingVertical: Theme.spacing.xs,
    borderRadius: Theme.borderRadius.full,
    marginRight: Theme.spacing.xs,
    marginBottom: Theme.spacing.xs,
    borderWidth: 1,
    borderColor: Colors.primary,
  },
  suggestionText: {
    fontSize: Theme.fontSize.xs,
    color: Colors.primary,
    fontWeight: Theme.fontWeight.semibold,
  },
  typingIndicator: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: Theme.spacing.md,
    paddingBottom: Theme.spacing.sm,
  },
  typingText: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textLight,
    marginLeft: Theme.spacing.xs,
    fontStyle: 'italic',
  },
  inputContainer: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    padding: Theme.spacing.md,
    backgroundColor: '#FFF',
    borderTopWidth: 1,
    borderTopColor: Colors.border,
  },
  input: {
    flex: 1,
    backgroundColor: Colors.background,
    borderRadius: Theme.borderRadius.lg,
    paddingHorizontal: Theme.spacing.md,
    paddingVertical: Theme.spacing.sm,
    paddingTop: Theme.spacing.sm,
    marginRight: Theme.spacing.sm,
    fontSize: Theme.fontSize.md,
    color: Colors.text,
    maxHeight: 100,
  },
  sendButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: Colors.primary,
    justifyContent: 'center',
    alignItems: 'center',
  },
  sendButtonDisabled: {
    backgroundColor: Colors.border,
  },
});

export default ChatbotScreen;
