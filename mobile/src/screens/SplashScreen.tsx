import React, {useEffect} from 'react';
import {View, Text, StyleSheet, Image} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {useAppDispatch, useAppSelector} from '@/store';
import {loadUser} from '@/store/slices/authSlice';
import {Colors, Theme} from '@/constants';

const SplashScreen = () => {
  const navigation = useNavigation();
  const dispatch = useAppDispatch();
  const {isAuthenticated} = useAppSelector(state => state.auth);

  useEffect(() => {
    const init = async () => {
      await dispatch(loadUser());
      
      setTimeout(() => {
        if (isAuthenticated) {
          navigation.navigate('Main' as never);
        } else {
          navigation.navigate('Login' as never);
        }
      }, 2000);
    };

    init();
  }, []);

  return (
    <View style={styles.container}>
      <View style={styles.logoContainer}>
        <Text style={styles.logo}>FreeOui</Text>
        <Text style={styles.tagline}>Découvrez les avantages près de chez vous</Text>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.primary,
    justifyContent: 'center',
    alignItems: 'center',
  },
  logoContainer: {
    alignItems: 'center',
  },
  logo: {
    fontSize: 48,
    fontWeight: 'bold',
    color: Colors.white,
    marginBottom: 8,
  },
  tagline: {
    fontSize: 16,
    color: Colors.white,
    opacity: 0.9,
  },
});

export default SplashScreen;
