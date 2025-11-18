import {createSlice, createAsyncThunk} from '@reduxjs/toolkit';
import {walletService} from '@/services';

interface WalletState {
  balance: number;
  transactions: any[];
  loading: boolean;
}

const initialState: WalletState = {
  balance: 0,
  transactions: [],
  loading: false,
};

export const fetchBalance = createAsyncThunk(
  'wallet/fetchBalance',
  async () => {
    const response = await walletService.getBalance();
    return response.data;
  },
);

const walletSlice = createSlice({
  name: 'wallet',
  initialState,
  reducers: {},
  extraReducers: builder => {
    builder.addCase(fetchBalance.fulfilled, (state, action) => {
      state.balance = action.payload.balance;
      state.transactions = action.payload.transactions;
    });
  },
});

export default walletSlice.reducer;
