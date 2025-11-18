import React, {useEffect} from 'react';
import {StatusBar, LogBox} from 'react-native';
import {SafeAreaProvider} from 'react-native-safe-area-context';
import {Provider} from 'react-redux';
import {GestureHandlerRootView} from 'react-native-gesture-handler';
import {store} from './src/store';
import {AppNavigator} from './src/navigation/AppNavigator';
import {Colors} from './src/constants/colors';

LogBox.ignoreAllLogs();

const App = () => {
  return (
    <GestureHandlerRootView style={{flex: 1}}>
      <Provider store={store}>
        <SafeAreaProvider>
          <StatusBar
            barStyle="light-content"
            backgroundColor={Colors.primary}
          />
          <AppNavigator />
        </SafeAreaProvider>
      </Provider>
    </GestureHandlerRootView>
  );
};

export default App;
