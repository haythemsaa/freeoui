import React, {useState} from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {useAppDispatch, useAppSelector} from '@/store';
import {login} from '@/store/slices/authSlice';
import {Colors, Theme} from '@/constants';

const LoginScreen = () => {
  const navigation = useNavigation();
  const dispatch = useAppDispatch();
  const {loading, error} = useAppSelector(state => state.auth);
  
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');

  const handleLogin = async () => {
    const result = await dispatch(login({phone_number: phone, password}));
    if (login.fulfilled.match(result)) {
      navigation.navigate('Main' as never);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
      <View style={styles.content}>
        <Text style={styles.title}>Bienvenue sur FreeOui</Text>
        <Text style={styles.subtitle}>Connectez-vous pour continuer</Text>

        <View style={styles.inputContainer}>
          <TextInput
            style={styles.input}
            placeholder="Numéro de téléphone"
            placeholderTextColor={Colors.gray400}
            value={phone}
            onChangeText={setPhone}
            keyboardType="phone-pad"
          />
          <TextInput
            style={styles.input}
            placeholder="Mot de passe"
            placeholderTextColor={Colors.gray400}
            value={password}
            onChangeText={setPassword}
            secureTextEntry
          />
        </View>

        {error && <Text style={styles.error}>{error}</Text>}

        <TouchableOpacity
          style={styles.button}
          onPress={handleLogin}
          disabled={loading}>
          <Text style={styles.buttonText}>
            {loading ? 'Connexion...' : 'Se connecter'}
          </Text>
        </TouchableOpacity>

        <TouchableOpacity style={styles.registerButton}>
          <Text style={styles.registerText}>
            Pas de compte ? <Text style={styles.registerLink}>S'inscrire</Text>
          </Text>
        </TouchableOpacity>
      </View>
    </KeyboardAvoidingView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.white,
  },
  content: {
    flex: 1,
    padding: Theme.spacing.xl,
    justifyContent: 'center',
  },
  title: {
    fontSize: Theme.fontSize.xxxl,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.textPrimary,
    marginBottom: Theme.spacing.sm,
  },
  subtitle: {
    fontSize: Theme.fontSize.md,
    color: Colors.textSecondary,
    marginBottom: Theme.spacing.xl,
  },
  inputContainer: {
    marginBottom: Theme.spacing.lg,
  },
  input: {
    backgroundColor: Colors.gray50,
    borderRadius: Theme.borderRadius.lg,
    padding: Theme.spacing.md,
    marginBottom: Theme.spacing.md,
    fontSize: Theme.fontSize.md,
    color: Colors.textPrimary,
  },
  button: {
    backgroundColor: Colors.primary,
    borderRadius: Theme.borderRadius.lg,
    padding: Theme.spacing.md,
    alignItems: 'center',
    marginBottom: Theme.spacing.md,
  },
  buttonText: {
    color: Colors.white,
    fontSize: Theme.fontSize.md,
    fontWeight: Theme.fontWeight.semibold,
  },
  registerButton: {
    alignItems: 'center',
  },
  registerText: {
    color: Colors.textSecondary,
  },
  registerLink: {
    color: Colors.primary,
    fontWeight: Theme.fontWeight.semibold,
  },
  error: {
    color: Colors.error,
    marginBottom: Theme.spacing.md,
    textAlign: 'center',
  },
});

export default LoginScreen;
