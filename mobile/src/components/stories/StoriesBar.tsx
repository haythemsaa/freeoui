import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Image,
} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {Colors} from '../../constants/colors';
import {Theme} from '../../constants/theme';
import Icon from 'react-native-vector-icons/Ionicons';

interface Story {
  id: string;
  merchant: {
    id: string;
    name: string;
    avatar_url?: string;
  };
  viewed: boolean;
}

interface StoriesBarProps {
  stories: Story[];
}

const StoriesBar: React.FC<StoriesBarProps> = ({stories}) => {
  const navigation = useNavigation();

  const renderStoryItem = (story: Story) => {
    return (
      <TouchableOpacity
        key={story.id}
        style={styles.storyItem}
        onPress={() =>
          navigation.navigate('StoryViewer' as never, {storyId: story.id} as never)
        }>
        <View
          style={[
            styles.storyAvatarContainer,
            story.viewed && styles.storyAvatarViewed,
          ]}>
          {story.merchant.avatar_url ? (
            <Image
              source={{uri: story.merchant.avatar_url}}
              style={styles.storyAvatar}
            />
          ) : (
            <View style={styles.storyAvatarPlaceholder}>
              <Icon name="business" size={20} color={Colors.primary} />
            </View>
          )}
        </View>
        <Text style={styles.storyName} numberOfLines={1}>
          {story.merchant.name}
        </Text>
      </TouchableOpacity>
    );
  };

  if (stories.length === 0) return null;

  return (
    <View style={styles.container}>
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}>
        {stories.map(renderStoryItem)}
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#FFF',
    paddingVertical: Theme.spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: Colors.border,
  },
  scrollContent: {
    paddingHorizontal: Theme.spacing.md,
  },
  storyItem: {
    alignItems: 'center',
    marginRight: Theme.spacing.md,
    width: 70,
  },
  storyAvatarContainer: {
    width: 64,
    height: 64,
    borderRadius: 32,
    padding: 3,
    borderWidth: 3,
    borderColor: Colors.primary,
    marginBottom: Theme.spacing.xs,
  },
  storyAvatarViewed: {
    borderColor: Colors.border,
  },
  storyAvatar: {
    width: '100%',
    height: '100%',
    borderRadius: 28,
  },
  storyAvatarPlaceholder: {
    width: '100%',
    height: '100%',
    borderRadius: 28,
    backgroundColor: Colors.primaryLight,
    justifyContent: 'center',
    alignItems: 'center',
  },
  storyName: {
    fontSize: Theme.fontSize.xs,
    color: Colors.text,
    textAlign: 'center',
  },
});

export default StoriesBar;
