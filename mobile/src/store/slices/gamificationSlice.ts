import {createSlice, createAsyncThunk} from '@reduxjs/toolkit';
import {gamificationService} from '@/services';
import {Challenge, Sticker} from '@/types';

interface GamificationState {
  challenges: Challenge[];
  stickers: Sticker[];
  stats: any;
  loading: boolean;
}

const initialState: GamificationState = {
  challenges: [],
  stickers: [],
  stats: null,
  loading: false,
};

export const fetchChallenges = createAsyncThunk(
  'gamification/fetchChallenges',
  async () => {
    const response = await gamificationService.getChallenges();
    return response.data || [];
  },
);

export const fetchStickers = createAsyncThunk(
  'gamification/fetchStickers',
  async () => {
    const response = await gamificationService.getMyStickers();
    return response.data || [];
  },
);

const gamificationSlice = createSlice({
  name: 'gamification',
  initialState,
  reducers: {},
  extraReducers: builder => {
    builder
      .addCase(fetchChallenges.fulfilled, (state, action) => {
        state.challenges = action.payload;
      })
      .addCase(fetchStickers.fulfilled, (state, action) => {
        state.stickers = action.payload;
      });
  },
});

export default gamificationSlice.reducer;
